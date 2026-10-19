<?php

namespace App\Services\Sms\Direct;

use App\Models\SmsProviderAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class DirectSmsProvider
{
    public function validateEndpoint(string $provider, string $url): string
    {
        $url = rtrim($url, '/');
        $p = parse_url($url);
        if (! $p || ($p['scheme'] ?? '') !== 'https' || isset($p['user'],$p['pass']) || isset($p['user']) || isset($p['port']) || isset($p['query']) || isset($p['fragment'])) {
            throw new RuntimeException('Select a supported HTTPS provider endpoint.');
        }
        $host = strtolower($p['host'] ?? '');
        $path = $p['path'] ?? '';
        if ($provider === 'termii' && in_array($host, config('direct_sms.termii_hosts'), true) && in_array($path, ['', '/api'], true)) {
            return 'https://'.$host;
        }
        if ($provider === 'bulksmsnigeria' && $host === 'www.bulksmsnigeria.com' && in_array($path, ['', '/api/v2', '/api/sandbox/v2'], true)) {
            return 'https://'.$host.($path === '/api/sandbox/v2' ? $path : '/api/v2');
        }
        throw new RuntimeException('The endpoint does not belong to the selected supported provider.');
    }

    private function request(SmsProviderAccount $account, string $method, string $path, array $data = []): array
    {
        $base = $this->validateEndpoint($account->provider, $account->base_url);
        $http = Http::acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(25);
        if ($account->provider === 'termii') {
            $data['api_key'] = $account->api_key;
        } else {
            $http = $http->withToken($account->api_key);
        }
        try {
            $r = $method === 'GET' ? $http->get($base.$path, $data) : $http->post($base.$path, $data);
            if (strlen($r->body()) > 2097152) {
                return ['ok' => false, 'uncertain' => $method === 'POST', 'code' => 'provider_response_invalid'];
            }
            $json = $r->json();
            if (! is_array($json)) {
                return ['ok' => false, 'uncertain' => $method === 'POST', 'code' => 'provider_response_invalid'];
            }
            $ok = $r->successful() && ($account->provider === 'termii' ? ($json['code'] ?? 'ok') === 'ok' : ($json['status'] ?? data_get($json, 'data.status', 'success')) === 'success');

            return ['ok' => $ok, 'uncertain' => $method === 'POST' && ($r->serverError() || $r->status() === 408), 'code' => $ok ? 'ok' : ('provider_http_'.$r->status()), 'data' => $json];
        } catch (Throwable) {
            return ['ok' => false, 'uncertain' => $method === 'POST', 'code' => 'provider_connection_unconfirmed'];
        }
    }

    public function connection(SmsProviderAccount $a): array
    {
        $r = $this->request($a, 'GET', $a->provider === 'termii' ? '/api/get-balance' : '/balance');

        return ['ok' => $r['ok'], 'code' => $r['code'], 'balance' => $r['ok'] ? ($a->provider === 'termii' ? data_get($r, 'data.balance') : data_get($r, 'data.balance.total_balance')) : null];
    }

    public function send(SmsProviderAccount $a, string $sender, string $body, array $phones): array
    {
        $phones = array_map(fn ($p) => ltrim($p, '+'), $phones);
        $r = $a->provider === 'termii' ? $this->request($a, 'POST', '/api/sms/send/bulk', ['to' => $phones, 'from' => $sender, 'sms' => $body, 'type' => 'plain', 'channel' => 'generic']) : $this->request($a, 'POST', '/sms', ['to' => implode(',', $phones), 'from' => $sender, 'body' => $body, 'gateway' => 'direct-refund', 'unicode' => true]);
        $ref = $a->provider === 'termii' ? data_get($r, 'data.message_id_str', data_get($r, 'data.message_id')) : data_get($r, 'data.data.message_id');
        if ($r['ok'] && (! is_scalar($ref) || (string) $ref === '')) {
            return ['ok' => false, 'uncertain' => true, 'code' => 'provider_reference_missing', 'reference' => null];
        }

        return ['ok' => $r['ok'], 'uncertain' => $r['uncertain'], 'code' => $r['code'], 'reference' => $r['ok'] ? substr((string) $ref, 0, 190) : null];
    }

    public function reports(SmsProviderAccount $a, string $reference): array
    {
        // Authenticated polling avoids trusting unsigned delivery callbacks.
        if ($a->provider === 'termii') {
            return $this->request($a, 'GET', '/api/sms/inbox', ['message_id' => $reference]);
        }

        return $this->request($a, 'GET', '/delivery-reports', ['message_id' => $reference, 'per_page' => 1000]);
    }

    public function delivery(string $status): string
    {
        $s = strtolower(trim($status));
        if (in_array($s, ['delivered', 'delivrd', 'success'], true)) {
            return 'delivered';
        }
        if (in_array($s, ['failed', 'undeliv', 'undelivered', 'expired', 'rejectd', 'rejected', 'dnd', 'blocked'], true)) {
            return 'failed';
        }

        return 'accepted';
    }
}
