<?php

namespace App\Services\Sms;

class SmsPhoneNormalizer
{
    public function normalize(?string $value): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', trim((string) $value));
        if ($phone === '') return null;

        if (str_starts_with($phone, '0')) $phone = '+234'.substr($phone, 1);
        elseif (str_starts_with($phone, '234')) $phone = '+'.$phone;

        return preg_match('/^\+[1-9]\d{7,14}$/', $phone) ? $phone : null;
    }

    public function prepare(array $records): array
    {
        $valid = [];
        $invalid = [];
        $duplicates = 0;

        foreach ($records as $item) {
            $record = is_array($item) ? $item : ['phone' => $item];
            $original = trim((string) ($record['phone'] ?? ''));
            $phone = $this->normalize($original);
            if ($phone === null) {
                $invalid[] = ['phone' => $original, 'name' => $record['name'] ?? null];
                continue;
            }
            if (isset($valid[$phone])) {
                $duplicates++;
                continue;
            }

            $record['phone'] = $phone;
            $record['metadata'] = array_filter(array_merge((array) ($record['metadata'] ?? []), [
                'original_phone' => $original !== $phone ? $original : null,
            ]), fn ($value) => $value !== null && $value !== '');
            $valid[$phone] = $record;
        }

        return [
            'recipients' => array_values($valid),
            'matched' => count($records),
            'valid' => count($valid),
            'invalid' => count($invalid),
            'duplicates' => $duplicates,
            'invalid_rows' => $invalid,
        ];
    }
}
