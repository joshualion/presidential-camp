<?php

use App\Models\IntegrityStatus;
use App\Services\ReleaseIntegrityService;
use App\Services\ReleaseManifestSigner;
use App\Services\ReleaseSigningKeyService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

function integrityFixturePath(): string
{
    $basePath = storage_path('framework/testing/release-integrity/'.(string) Str::uuid());
    File::ensureDirectoryExists($basePath);

    return $basePath;
}

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/release-integrity'));
});

test('integrity checker does not crash when manifest is missing in development', function () {
    $service = new ReleaseIntegrityService(app(ReleaseManifestSigner::class), integrityFixturePath());

    $result = $service->check();

    expect($result['manifest_present'])->toBeFalse()
        ->and($result['tampered'])->toBeFalse()
        ->and($result['update_entitlement_state'])->toBe('manifest_missing')
        ->and(IntegrityStatus::query()->latest('id')->first()->manifest_present)->toBeFalse();
});

test('integrity checker detects modified protected file', function () {
    $basePath = integrityFixturePath();
    $protectedPath = 'app/Services/LocalLicenseService.php';
    File::ensureDirectoryExists($basePath.'/app/Services');
    File::put($basePath.'/'.$protectedPath, '<?php echo "clean";');

    $manifest = [
        'version' => 'v1.0.1',
        'protected_files' => [
            $protectedPath => hash_file('sha256', $basePath.'/'.$protectedPath),
        ],
    ];

    File::put($basePath.'/release-manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    File::put($basePath.'/'.$protectedPath, '<?php echo "tampered";');

    $service = new ReleaseIntegrityService(app(ReleaseManifestSigner::class), $basePath);
    $result = $service->check();

    expect($result['manifest_present'])->toBeTrue()
        ->and($result['manifest_signature_valid'])->toBeNull()
        ->and($result['protected_files_valid'])->toBeFalse()
        ->and($result['tampered'])->toBeTrue()
        ->and($result['update_entitlement_state'])->toBe('tampered')
        ->and($result['tamper_details'][0]['file'])->toBe($protectedPath);
});

test('integrity checker verifies signed manifest', function () {
    $basePath = integrityFixturePath();
    $protectedPath = 'app/Services/LocalLicenseService.php';
    File::ensureDirectoryExists($basePath.'/app/Services');
    File::put($basePath.'/'.$protectedPath, '<?php echo "clean";');

    $signingConfig = [
        'keys_dir' => 'storage/app/release-keys',
        'private_key_path' => 'storage/app/release-keys/campaign_release_private.pem',
        'public_key_path' => 'storage/app/release-keys/campaign_release_public.pem',
        'public_key_id' => 'test',
        '_base_path' => $basePath,
    ];
    (new ReleaseSigningKeyService($basePath, $signingConfig))->generate();
    config(['release.signing.public_key_path' => $signingConfig['public_key_path']]);

    $signed = app(ReleaseManifestSigner::class)->sign([
        'version' => 'v1.0.1',
        'protected_files' => [
            $protectedPath => hash_file('sha256', $basePath.'/'.$protectedPath),
        ],
    ], $signingConfig);

    File::put($basePath.'/release-manifest.json', json_encode($signed['manifest'], JSON_PRETTY_PRINT));

    $signer = new class extends ReleaseManifestSigner {
        public function __construct(public array $testSigningConfig = [])
        {
        }

        public function verify(array $manifest, ?string $publicKey = null, ?array $signingConfig = null): ?bool
        {
            return parent::verify($manifest, $publicKey, $this->testSigningConfig);
        }
    };
    $signer->testSigningConfig = $signingConfig;

    $result = (new ReleaseIntegrityService($signer, $basePath))->check();

    expect($result['manifest_signature_valid'])->toBeTrue()
        ->and($result['protected_files_valid'])->toBeTrue()
        ->and($result['tampered'])->toBeFalse()
        ->and($result['update_entitlement_state'])->toBe('clean');
});
