<?php

namespace App\Services\Sms;

use App\Models\SmsAuditLog;
use Illuminate\Database\Eloquent\Model;

class SmsAuditService
{
    public function record(string $event, ?Model $subject = null, array $metadata = [], ?string $description = null): void
    {
        SmsAuditLog::create([
            'event' => $event,
            'actor_id' => auth()->id(),
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $this->redact($metadata),
            'ip_address' => request()?->ip(),
        ]);
    }

    private function redact(array $metadata): array
    {
        foreach (array_keys($metadata) as $key) {
            if (str_contains(strtolower((string) $key), 'secret') || str_contains(strtolower((string) $key), 'signature')) {
                $metadata[$key] = '[REDACTED]';
            }
        }
        return $metadata;
    }
}
