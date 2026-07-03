<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SMTPSettingsSeeder extends Seeder
{
    /**
     * Run the seeder.
     */
    public function run(): void
    {
        $mailer = strtolower(trim((string) config('mail.default'))) ?: 'log';
        $smtp = (array) config('mail.mailers.smtp', []);

        DB::table('s_m_t_p_settings')->updateOrInsert(['id' => 1], [
            'mailer' => $mailer,
            'host' => $smtp['host'] ?? null,
            'port' => $smtp['port'] ?? null,
            'username' => $smtp['username'] ?? null,
            'password' => $smtp['password'] ?? null,
            'encryption' => $smtp['encryption'] ?? null,
            'from_address' => config('mail.from.address'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
