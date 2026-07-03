<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallerMailSettingsService
{
    public function persist(array $mail): void
    {
        if (!Schema::hasTable('s_m_t_p_settings')) {
            return;
        }

        $mailer = $this->value($mail, 'MAIL_MAILER') ?: 'log';

        DB::table('s_m_t_p_settings')->updateOrInsert(['id' => 1], [
            'mailer' => strtolower($mailer),
            'host' => $this->value($mail, 'MAIL_HOST'),
            'port' => $this->value($mail, 'MAIL_PORT'),
            'username' => $this->value($mail, 'MAIL_USERNAME'),
            'password' => $this->value($mail, 'MAIL_PASSWORD'),
            'encryption' => $this->value($mail, 'MAIL_ENCRYPTION'),
            'from_address' => $this->value($mail, 'MAIL_FROM_ADDRESS'),
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function value(array $mail, string $key): ?string
    {
        if (!array_key_exists($key, $mail) || $mail[$key] === null) {
            return null;
        }

        $value = trim((string) $mail[$key]);

        return $value === '' ? null : $value;
    }
}
