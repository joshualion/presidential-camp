<?php

use Illuminate\Support\Facades\File;

test('distributed environment examples use the bundled official release public key', function () {
    foreach (['.env.example', '.env.production.example'] as $example) {
        $contents = File::get(base_path($example));
        preg_match('/^CAMPAIGN_RELEASE_PUBLIC_KEY_PATH=(.+)$/m', $contents, $match);
        $path = trim($match[1] ?? '');

        expect($path)->toBe('config/release-public.pem')
            ->and(File::isReadable(base_path($path)))->toBeTrue()
            ->and(openssl_pkey_get_public(File::get(base_path($path))))->not->toBeFalse();
    }
});
