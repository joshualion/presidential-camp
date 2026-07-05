<?php

namespace App\Services\Sms;

use App\Models\SystemSetting;
use App\Services\PackageScopeService;
use App\Services\ServerFingerprintService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class PortalSmsClient
{
    public function __construct(
        private PackageScopeService $scope,
        private ServerFingerprintService $fingerprint,
    ) {}

    public function estimate(array $payload): array
    {
        $settings = $this->settings();
        $payload['client_key'] = (string) $settings?->portal_sms_client_key;
        return $this->operation('POST', 'estimate', $payload);
    }

    public function createBatch(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->idempotentOperation('POST', 'batches.create', $payload, $idempotencyKey);
    }

    public function getWallet(): array { return $this->operation('GET', 'wallet.show'); }
    public function listSenderIds(): array { return $this->operation('GET', 'sender_ids.index'); }

    public function diagnoseConnection(): array
    {
        $wallet = $this->getWallet();
        if ($wallet['ok'] ?? false) {
            $wallet['data']['diagnostic_endpoint'] = 'wallet';
            $wallet['data']['wallet_available'] = true;
            $wallet['message'] = 'Portal SMS connection and master wallet access are valid.';
            return $wallet;
        }
        if (($wallet['code'] ?? null) !== 'wallet_not_found') return $wallet;

        $senderIds = $this->listSenderIds();
        if (!($senderIds['ok'] ?? false)) return $senderIds;
        return ['ok' => true, 'status' => 200, 'code' => null, 'message' => 'Portal SMS authentication is valid, but no active installation master wallet is available.', 'data' => ['diagnostic_endpoint' => 'sender_ids', 'wallet_available' => false], 'meta' => [], 'idempotency_key' => null];
    }

    public function requestSenderId(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->idempotentOperation('POST', 'sender_ids.create', $payload, $idempotencyKey);
    }

    public function listBatches(array $filters = []): array { return $this->operation('GET', 'batches.index', $filters); }
    public function getBatch(string $batchReference): array { return $this->operation('GET', 'batches.show', [], ['batch_reference' => $batchReference]); }
    public function getBatchMessages(string $batchReference, array $filters = []): array { return $this->operation('GET', 'batches.messages', $filters, ['batch_reference' => $batchReference]); }

    public function initiateTopup(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->idempotentOperation('POST', 'topups.initiate', $payload, $idempotencyKey);
    }

    public function listAllocations(array $filters = []): array { return $this->operation('GET', 'allocations.index', $filters); }
    public function getAllocation(string $allocationReference): array { return $this->operation('GET', 'allocations.show', [], ['allocation_reference' => $allocationReference]); }

    public function markAllocationSynced(string $allocationReference): array
    {
        return $this->operation('POST', 'allocations.mark_synced', [], ['allocation_reference' => $allocationReference], 'core-allocation-sync-'.$allocationReference);
    }

    public function createCreditRequest(array $payload, ?string $idempotencyKey = null): array
    {
        return $this->idempotentOperation('POST', 'credit_requests.create', $payload, $idempotencyKey);
    }

    public function listCreditRequests(array $filters = []): array { return $this->operation('GET', 'credit_requests.index', $filters); }
    public function getCreditRequest(string $requestReference): array { return $this->operation('GET', 'credit_requests.show', [], ['request_reference' => $requestReference]); }

    public function acknowledgeMessageSync(array $payload): array
    {
        $batchReference = (string) ($payload['batch_reference'] ?? '');
        if ($batchReference === '') return $this->error('validation_failed', 'A batch reference is required for message synchronization acknowledgement.');
        $references = array_values((array) ($payload['message_references'] ?? []));
        $key = 'core-message-sync-'.hash('sha256', $batchReference.'|'.implode('|', $references));
        return $this->operation('POST', 'batches.messages.mark_synced', ['message_references' => $references], ['batch_reference' => $batchReference], $key);
    }

    private function idempotentOperation(string $method, string $operation, array $payload, ?string $idempotencyKey): array
    {
        $idempotencyKey ??= (string) Str::uuid();
        $payload['idempotency_key'] = $idempotencyKey;
        return $this->operation($method, $operation, $payload, [], $idempotencyKey);
    }

    private function operation(string $method, string $operation, array $payload = [], array $parameters = [], ?string $idempotencyKey = null): array
    {
        $template = config('portal_sms.endpoints.'.$operation);
        if (!is_string($template) || $template === '') {
            return $this->error('portal_contract_unsupported', 'The installed portal contract does not expose this SMS operation.', 501, ['operation' => $operation], $idempotencyKey);
        }
        $path = $template;
        foreach ($parameters as $name => $value) $path = str_replace('{'.$name.'}', rawurlencode((string) $value), $path);
        if (preg_match('/\{[^}]+\}/', $path)) return $this->error('portal_contract_invalid', 'The SMS endpoint could not be constructed.', 0, [], $idempotencyKey);

        return $this->send($method, $path, $payload, $idempotencyKey);
    }

    private function send(string $method, string $path, array $payload, ?string $idempotencyKey): array
    {
        $settings = $this->settings();
        if (!$settings?->portal_sms_enabled) return $this->error('sms_module_disabled', 'SMS is disabled. Ask a super administrator to complete portal SMS setup.', 0, [], $idempotencyKey);

        $baseUrl = rtrim((string) ($settings->portal_sms_base_url ?: config('portal_sms.base_url')), '/');
        $clientKey = trim((string) $settings->portal_sms_client_key);
        try { $secret = (string) $settings->portal_sms_secret; }
        catch (Throwable) { return $this->error('sms_secret_unreadable', 'The saved portal SMS secret cannot be decrypted. Update it in SMS settings.', 0, [], $idempotencyKey); }
        if ($baseUrl === '' || $clientKey === '' || $secret === '') return $this->error('sms_not_configured', 'Portal SMS credentials are incomplete.', 0, [], $idempotencyKey);
        $urlParts = parse_url($baseUrl);
        if (!is_array($urlParts)
            || !in_array(strtolower((string) ($urlParts['scheme'] ?? '')), ['http', 'https'], true)
            || empty($urlParts['host'])
            || isset($urlParts['user'])
            || isset($urlParts['pass'])
            || isset($urlParts['query'])
            || isset($urlParts['fragment'])) {
            return $this->error('portal_base_url_invalid', 'Portal SMS base URL must be an HTTP(S) origin and API path without credentials, query, or fragment.', 0, [], $idempotencyKey);
        }
        $requiredSuffix = rtrim((string) config('portal_sms.required_path_suffix'), '/');
        if ($requiredSuffix !== '' && !str_ends_with(rtrim((string) parse_url($baseUrl, PHP_URL_PATH), '/'), $requiredSuffix)) {
            return $this->error('portal_base_url_invalid', 'Portal SMS base URL must end with '.$requiredSuffix.'.', 0, [], $idempotencyKey);
        }

        $method = strtoupper($method);
        $requestPayload = $method === 'GET' ? $payload : array_merge($payload, ['core_identity' => $this->identityContext()]);
        try { $body = $method === 'GET' ? '' : $this->canonicalJson($requestPayload); }
        catch (\JsonException) { return $this->error('validation_failed', 'The SMS request contains text that cannot be encoded safely.', 0, [], $idempotencyKey); }
        $timestamp = (string) now()->timestamp;
        $basePath = rtrim((string) parse_url($baseUrl, PHP_URL_PATH), '/');
        $signedPath = ($basePath === '' ? '' : '/'.ltrim($basePath, '/')).'/'.ltrim($path, '/');
        $query = $method === 'GET' && $requestPayload !== []
            ? http_build_query($requestPayload, '', '&', PHP_QUERY_RFC3986)
            : '';
        $signedTarget = $signedPath.($query === '' ? '' : '?'.$query);
        $canonical = implode("\n", [$timestamp, $method, $signedTarget, hash('sha256', $body), $idempotencyKey ?? '']);
        $signature = hash_hmac((string) config('portal_sms.hmac.algorithm', 'sha256'), $canonical, $secret);
        $headerNames = (array) config('portal_sms.hmac.headers');
        $headers = [
            $headerNames['client_key'] ?? 'X-CM-Client-Key' => $clientKey,
            $headerNames['timestamp'] ?? 'X-CM-Timestamp' => $timestamp,
            $headerNames['signature'] ?? 'X-CM-Signature' => $signature,
        ];
        if ($idempotencyKey !== null && $idempotencyKey !== '') $headers[$headerNames['idempotency_key'] ?? 'X-CM-Idempotency-Key'] = $idempotencyKey;

        try {
            $pending = Http::timeout(20)->connectTimeout(7)->acceptJson()->asJson()->withHeaders($headers);
            $response = $method === 'GET'
                ? $pending->get($baseUrl.$path.($query === '' ? '' : '?'.$query))
                : $pending->withBody($body, 'application/json')->send($method, $baseUrl.$path);
            $decoded = $response->json();
            $data = is_array($decoded) ? $decoded : [];
            $code = $this->errorCode($response->status(), $data);
            if (!$response->successful()) return $this->error($code, $this->friendlyMessage($code), $response->status(), $this->safeErrorData($data), $idempotencyKey);

            return ['ok' => true, 'status' => $response->status(), 'code' => null, 'message' => (string) (data_get($data, 'message') ?? 'Request completed.'), 'data' => data_get($data, 'data', $data), 'meta' => data_get($data, 'meta', []), 'idempotency_key' => $idempotencyKey];
        } catch (ConnectionException) {
            return $this->error('portal_unreachable', 'Campaign Manager Portal is unreachable. Check the connection and try again.', 0, [], $idempotencyKey);
        } catch (Throwable) {
            return $this->error('portal_client_error', 'The portal SMS request could not be completed.', 0, [], $idempotencyKey);
        }
    }

    private function settings(): ?SystemSetting { return SystemSetting::query()->first(); }

    private function identityContext(): array
    {
        $license = $this->scope->localLicense();
        return array_filter(['license_key' => $license?->license_key, 'activation_id' => $license?->activation_id, 'app_url' => config('app.url'), 'domain' => parse_url((string) config('app.url'), PHP_URL_HOST), 'installation_fingerprint' => $this->fingerprint->make(), 'package' => $this->scope->packageType(), 'scope' => $this->scope->current()->toArray()], fn ($value) => $value !== null && $value !== '');
    }

    private function canonicalJson(array $payload): string
    {
        $sort = function (&$value) use (&$sort): void { if (!is_array($value)) return; foreach ($value as &$item) $sort($item); if (!array_is_list($value)) ksort($value); };
        $sort($payload);
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function errorCode(int $status, array $data): string
    {
        $error = data_get($data, 'error');
        if (is_string($error) && $error !== '') return $error;
        $code = data_get($data, 'code') ?? data_get($data, 'error.code');
        if (is_string($code) && $code !== '') return $code;
        if ($status === 401) {
            $message = strtolower((string) data_get($data, 'message'));
            if (str_contains($message, 'signature') || str_contains($message, 'timestamp') || str_contains($message, 'signed request')) return 'invalid_signature';
            if (str_contains($message, 'linked campaign manager installation activation is not active')) return 'suspended_api_client';
            return 'unauthorized_api_client';
        }
        if ($status === 422) return 'validation_failed';
        return 'portal_error';
    }

    private function friendlyMessage(string $code): string
    {
        return match ($code) {
            'insufficient_wallet_balance' => 'The portal master wallet has insufficient balance.',
            'sender_id_not_approved' => 'The selected sender ID is not approved.',
            'sms_route_not_configured' => 'The selected SMS route is not configured.',
            'provider_not_configured' => 'No provider is currently available for this SMS route.',
            'provider_unavailable', 'sms_provider_unavailable' => 'The SMS provider is temporarily unavailable. Try again later.',
            'duplicate_idempotency_key' => 'This idempotency key was already used for a different request.',
            'invalid_recipients' => 'No valid SMS recipients remained after validation.',
            'invalid_signature' => 'Portal SMS request signing was rejected. Verify the secret, API base URL, and server clock.',
            'unauthorized_api_client' => 'The portal SMS API client was not authorized.',
            'suspended_api_client' => 'The portal SMS API client or linked activation is inactive.',
            'validation_error', 'validation_failed' => 'The portal rejected one or more SMS request fields.',
            'wallet_not_found' => 'No active installation master SMS wallet is available in the portal.',
            'billing_review_required' => 'Portal settlement requires billing review before this batch can be finalized.',
            default => 'The portal could not complete the SMS request.',
        };
    }

    private function safeErrorData(array $data): array
    {
        return array_filter(['errors' => data_get($data, 'errors'), 'retryable' => data_get($data, 'retryable')], fn ($value) => $value !== null);
    }

    private function error(string $code, string $message, int $status = 0, array $data = [], ?string $idempotencyKey = null): array
    {
        return compact('status', 'code', 'message', 'data') + ['ok' => false, 'meta' => [], 'idempotency_key' => $idempotencyKey];
    }
}
