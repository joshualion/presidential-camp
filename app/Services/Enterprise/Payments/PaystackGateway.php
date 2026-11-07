<?php

namespace App\Services\Enterprise\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PaystackGateway implements PaymentGateway
{
    public function checkConnection(#[\SensitiveParameter] string $secret): void
    {
        $this->request($secret, 'GET', '/balance');
    }

    public function initializeCollection(#[\SensitiveParameter] string $secret, array $details): array
    {
        return $this->request($secret, 'POST', '/transaction/initialize', $details);
    }

    public function listSettlements(#[\SensitiveParameter] string $secret, string $from, string $to): array
    {
        $query = http_build_query(['from' => $from, 'to' => $to, 'status' => 'success', 'subaccount' => 'none', 'perPage' => 100]);
        return $this->request($secret, 'GET', '/settlement?'.$query);
    }

    public function listSettlementTransactions(#[\SensitiveParameter] string $secret, int $settlementId): array
    {
        return $this->request($secret, 'GET', '/settlement/'.$settlementId.'/transactions?perPage=2');
    }

    public function fetchRefund(#[\SensitiveParameter] string $secret, int $refundId): array
    {
        return $this->request($secret, 'GET', '/refund/'.$refundId);
    }

    public function verifyCollection(#[\SensitiveParameter] string $secret, string $reference): array
    {
        return $this->request($secret, 'GET', '/transaction/verify/'.rawurlencode($reference));
    }

    public function createRecipient(#[\SensitiveParameter] string $secret, #[\SensitiveParameter] array $details): array
    {
        return $this->request($secret, 'POST', '/transferrecipient', $details);
    }

    public function fetchRecipient(#[\SensitiveParameter] string $secret, string $code): array
    {
        return $this->request($secret, 'GET', '/transferrecipient/'.rawurlencode($code));
    }

    public function initiateTransfer(#[\SensitiveParameter] string $secret, array $details): array
    {
        return $this->request($secret, 'POST', '/transfer', $details);
    }

    public function initiateBulkTransfer(#[\SensitiveParameter] string $secret, array $details): array
    {
        return $this->request($secret, 'POST', '/transfer/bulk', $details);
    }

    public function verifyTransfer(#[\SensitiveParameter] string $secret, string $reference): array
    {
        return $this->request($secret, 'GET', '/transfer/verify/'.rawurlencode($reference));
    }

    public function authenticates(string $body, string $header, string $legacy, #[\SensitiveParameter] ?string $webhookKey, #[\SensitiveParameter] string $secret): bool
    {
        if ($webhookKey === null && $header === '') {
            return preg_match('/^[0-9a-f]{128}$/D', $legacy) && hash_equals(hash_hmac('sha512', $body, $secret), $legacy);
        }
        if ($webhookKey === null || strlen($header) > 4096) {
            return false;
        }
        $time = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$name, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($name === 't') {
                if ($time !== null || !preg_match('/^[1-9][0-9]{0,10}$/D', $value)) {
                    return false;
                }
                $time = (int) $value;
            } elseif ($name === 'v1' && preg_match('/^[a-zA-Z0-9_-]+:([0-9a-f]{128})$/D', $value, $match)) {
                $signatures[] = $match[1];
            }
        }
        if ($time === null || abs(now()->timestamp - $time) > 300) {
            return false;
        }
        $expected = hash_hmac('sha512', $time.'.'.$body, $webhookKey);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }
        return false;
    }

    private function request(#[\SensitiveParameter] string $secret, string $method, string $path, array $payload = []): array
    {
        if ($method === 'POST' && !str_starts_with($secret, 'sk_test_')) {
            throw ValidationException::withMessages(['paystack' => 'Campaign payment execution is available in test mode only.']);
        }
        try {
            $response = Http::withToken($secret)->acceptJson()->withoutRedirecting()->timeout(15)
                ->send($method, 'https://api.paystack.co'.$path, $payload ? ['json' => $payload] : []);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['paystack' => 'Paystack could not be reached. Verify the saved reference before retrying.']);
        }
        if (!$response->successful() || $response->json('status') !== true || !is_array($response->json('data'))) {
            throw ValidationException::withMessages(['paystack' => 'Paystack did not confirm the request. Check its reference or merchant settings.']);
        }
        return $response->json('data');
    }
}
