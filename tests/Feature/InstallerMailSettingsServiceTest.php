<?php

use App\Services\Installer\InstallerMailSettingsService;
use Illuminate\Support\Facades\DB;

test('installer mail settings replace seeded smtp placeholders', function () {
    DB::table('s_m_t_p_settings')->insert([
        'id' => 1,
        'mailer' => 'smtp',
        'host' => 'smtp.gmail.com',
        'port' => '587',
        'username' => 'youremail@gmail.com',
        'password' => 'your-app-password',
        'encryption' => 'tls',
        'from_address' => 'youremail@gmail.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(InstallerMailSettingsService::class)->persist([
        'MAIL_MAILER' => 'smtp',
        'MAIL_HOST' => 'mail.client.example',
        'MAIL_PORT' => 465,
        'MAIL_USERNAME' => 'notifications@client.example',
        'MAIL_PASSWORD' => 'secret-password',
        'MAIL_ENCRYPTION' => 'ssl',
        'MAIL_FROM_ADDRESS' => 'campaign@client.example',
    ]);

    $settings = DB::table('s_m_t_p_settings')->where('id', 1)->first();

    expect($settings->mailer)->toBe('smtp')
        ->and($settings->host)->toBe('mail.client.example')
        ->and($settings->port)->toBe('465')
        ->and($settings->username)->toBe('notifications@client.example')
        ->and($settings->password)->toBe('secret-password')
        ->and($settings->encryption)->toBe('ssl')
        ->and($settings->from_address)->toBe('campaign@client.example');
});

test('installer mail settings use a safe log mailer when mail is skipped', function () {
    app(InstallerMailSettingsService::class)->persist([]);

    $settings = DB::table('s_m_t_p_settings')->where('id', 1)->first();

    expect($settings->mailer)->toBe('log')
        ->and($settings->host)->toBeNull()
        ->and($settings->password)->toBeNull();
});
