<?php

use Symfony\Component\Process\Process;

test('the bundled entitlement trust is the authenticated existing public signing identity', function () {
    $pem = file_get_contents(config_path('enterprise-public.pem'));
    expect($pem)->not->toContain('PRIVATE KEY');
    $details = openssl_pkey_get_details(openssl_pkey_get_public($pem));
    expect($details['type'])->toBe(OPENSSL_KEYTYPE_RSA)->and($details['bits'])->toBe(3072);
    $der = base64_decode(preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s/', '', $pem), true);
    expect(hash('sha256', $der))->toBe('e56a049a293cb6c7591769c538433a9e557457ca849e692828247ac6b35c1872');
});

test('blank legacy environment configuration automatically loads the signed bundled public key', function () {
    $process = new Process([PHP_BINARY, '-c', php_ini_loaded_file(), '-r', 'require "vendor/autoload.php"; $c=require "config/enterprise.php"; echo hash("sha256", $c["public_key"]);'], base_path(), ['ENTERPRISE_ENTITLEMENT_PUBLIC_KEY' => '']);
    $process->mustRun();
    expect(trim($process->getOutput()))->toBe(hash_file('sha256', config_path('enterprise-public.pem')));
});
