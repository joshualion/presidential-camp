<?php

use App\Services\ReleaseBuildService;
use App\Services\ReleaseReadinessService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

function releaseBuildFixture(array $omit = [], array $extraFiles = []): array
{
    $basePath = storage_path('framework/testing/release-build/'.(string) Str::uuid());
    $config = config('release');
    $fixtureCounts = [
        'regions' => 1,
        'states' => 1,
        'senatorial_districts' => 1,
        'federal_constituencies' => 1,
        'lgas' => 1,
        'wards' => 1,
        'polling_units' => 1,
    ];
    $config['profiles']['full']['expected_location_records'] = $fixtureCounts;
    $config['profiles']['community']['expected_location_records'] = $fixtureCounts;

    File::ensureDirectoryExists($basePath);

    foreach ($config['include_roots'] as $root) {
        if (in_array($root, $config['required_files'], true) || str_contains($root, '.')) {
            continue;
        }

        File::ensureDirectoryExists($basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $root));
    }

    foreach ($config['writable_directories'] as $directory) {
        $path = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $directory);
        File::ensureDirectoryExists($path);
        File::put($path.DIRECTORY_SEPARATOR.'.gitkeep', '');
    }

    $requiredFiles = array_merge(
        $config['required_files'],
        $config['required_location_csvs'],
        $config['profiles']['community']['required_location_csvs'] ?? []
    );

    foreach ($requiredFiles as $file) {
        if (in_array($file, $omit, true)) {
            continue;
        }

        releaseBuildPut($basePath, $file, match ($file) {
            '.env.example', '.env.production.example' => releaseBuildEnvExample(),
            'public/build/manifest.json' => '{"resources/js/app.js":{"file":"assets/app.js"}}',
            default => str_contains($file, '.csv') ? releaseBuildCsvFixture($file) : 'fixture',
        });
    }

    releaseBuildPut($basePath, 'app/Models/.gitkeep', '');
    releaseBuildPut($basePath, 'config/app.php', '<?php return [];');
    releaseBuildPut($basePath, 'routes/web.php', '<?php');
    releaseBuildPut($basePath, 'resources/views/.gitkeep', '');
    releaseBuildPut($basePath, 'database/migrations/.gitkeep', '');
    releaseBuildPut($basePath, 'public/build/assets/app.js', 'console.log("fixture");');
    releaseBuildPut($basePath, 'docs/pre-release-cleanup-checklist.md', 'internal');

    foreach ($extraFiles as $file => $contents) {
        releaseBuildPut($basePath, is_int($file) ? $contents : $file, is_int($file) ? 'local-only' : $contents);
    }

    return [$basePath, $config];
}

function releaseBuildPut(string $basePath, string $relativePath, string $contents): void
{
    $path = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $contents);
}

function releaseBuildEnvExample(): string
{
    return implode("\n", [
        'APP_KEY=',
        'APP_DEBUG=false',
        'DEPLOYMENT_MODE=self_hosted',
        'INSTALLER_ENABLED=true',
        'CAMPAIGN_PORTAL_API_BASE=https://campaignmanager.ng/api/v2',
        'BROADCAST_CONNECTION=log',
        'QUEUE_CONNECTION=sync',
        'CACHE_STORE=file',
        'SESSION_DRIVER=file',
    ]);
}

function releaseBuildCsvFixture(string $file): string
{
    return match (basename($file)) {
        'states.csv' => "country_name,region_name,state_code,name\nNigeria,South East,1,Abia\n",
        'senatorial_districts.csv' => "state_code,state_name,name\n1,Abia,Abia North\n",
        'federal_constituencies.csv' => "state_code,state_name,senatorial_district_name,name\n1,Abia,Abia North,Arochukwu/Ohafia\n",
        'local_government_areas.csv' => "state_code,state_name,lga_code,name,senatorial_district_name,federal_constituency_name\n1,Abia,3,Arochukwu,Abia North,Arochukwu/Ohafia\n",
        'wards.csv' => "state_code,state_name,lga_code,lga_name,ward_code,name\n1,Abia,3,Arochukwu,1,Ovukwu\n",
        'polling_units.csv' => "state_code,state_name,lga_code,lga_name,ward_code,ward_name,pu_code,inec_full_code,name,senatorial_district_name,federal_constituency_name\n1,Abia,3,Arochukwu,1,Ovukwu,18,01/03/01/018,Ndi Okereke Town Hall,Abia North,Arochukwu/Ohafia\n",
        default => "name\nfixture\n",
    };
}

function releaseBuildService(string $basePath, array $config): ReleaseBuildService
{
    $readiness = new ReleaseReadinessService($basePath, $config);

    return new ReleaseBuildService($readiness, $basePath, $config);
}

function releaseBuildZipNames(string $zipPath): array
{
    $zip = new ZipArchive();
    $zip->open($zipPath);

    $names = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $names[] = str_replace('\\', '/', ltrim((string) $zip->getNameIndex($index), './'));
    }

    $zip->close();

    return $names;
}

function releaseBuildZipContents(string $zipPath, string $name): string
{
    $zip = new ZipArchive();
    $zip->open($zipPath);
    $contents = $zip->getFromName($name);
    $zip->close();

    return (string) $contents;
}

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/release-build'));
});

test('dry-run does not create zip', function () {
    [$basePath, $config] = releaseBuildFixture();
    $result = releaseBuildService($basePath, $config)->dryRun([
        'profile' => 'full',
        'version' => '1.0.0',
        'channel' => 'stable',
    ]);

    expect($result['dry_run'])->toBeTrue()
        ->and(File::exists($result['zip_path']))->toBeFalse()
        ->and($result['included_count'])->toBeGreaterThan(0)
        ->and($result['zip_path'])->toContain('builds'.DIRECTORY_SEPARATOR.'releases');
});

test('release build excludes sensitive local files and upload artifacts', function () {
    [$basePath, $config] = releaseBuildFixture([], [
        '.env' => 'APP_KEY=secret',
        'storage/app/installed.lock' => 'installed',
        'public/uploads/community/photos/test.jpg' => 'photo',
        'public/uploads/community/videos/test.mp4' => 'video',
        'public/uploads/member_images/member.jpg' => 'member',
    ]);

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'channel' => 'stable',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->not->toContain('.env')
        ->and($names)->not->toContain('storage/app/installed.lock')
        ->and($names)->not->toContain('public/uploads/community/photos/test.jpg')
        ->and($names)->not->toContain('public/uploads/community/videos/test.mp4')
        ->and($names)->not->toContain('public/uploads/member_images/member.jpg')
        ->and($names)->toContain('public/uploads/community/photos/.gitkeep')
        ->and($names)->toContain('public/uploads/community/videos/.gitkeep')
        ->and($names)->toContain('public/uploads/member_images/.gitkeep');
});

test('release build includes bootstrap csv build manifest docs and vendor autoload', function () {
    [$basePath, $config] = releaseBuildFixture();
    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'channel' => 'stable',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->toContain('storage/app/imports/campaign-locations/bootstrap/polling_units.csv')
        ->and($names)->not->toContain('storage/app/imports/inec/prepared/all/polling_units.csv')
        ->and($names)->toContain('public/build/manifest.json')
        ->and($names)->toContain('docs/self-hosted-installation-guide.md')
        ->and($names)->toContain('vendor/autoload.php')
        ->and($names)->toContain('README-SELF-HOSTED.md')
        ->and($names)->toContain('LICENSE.txt')
        ->and($names)->toContain('EULA.txt')
        ->and($names)->toContain('release-manifest.json')
        ->and($names)->toContain('checksums.txt')
        ->and($names)->toContain('storage/logs/.gitkeep')
        ->and($names)->not->toContain('docs/pre-release-cleanup-checklist.md');
});

test('manifest contains version channel checksum protected files and portal provisioning fields', function () {
    [$basePath, $config] = releaseBuildFixture();
    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'channel' => 'stable',
        'output' => $basePath.'/builds',
    ]);

    $manifest = json_decode(File::get($result['manifest_path']), true);

    expect($manifest['version'])->toBe('1.0.0')
        ->and($manifest['product'])->toBe('Campaign Manager')
        ->and($manifest['profile'])->toBe('full')
        ->and($manifest['channel'])->toBe('stable')
        ->and($manifest['checksum_sha256'])->toBe($result['checksum_sha256'])
        ->and($manifest['package_type'])->toBe('universal_core')
        ->and($manifest['includes_full_location_data'])->toBeFalse()
        ->and($manifest['includes_bootstrap_location_data'])->toBeTrue()
        ->and($manifest['bundled_location_data'])->toBeFalse()
        ->and($manifest['portal_location_provisioning'])->toBeTrue()
        ->and($manifest['deployment_type'])->toBe('self_hosted')
        ->and($manifest)->toHaveKey('protected_files')
        ->and($manifest)->toHaveKey('checksums')
        ->and($manifest['update_entitlement_warning'])->toContain('blocked or flagged')
        ->and($manifest)->toHaveKey('location_data')
        ->and(json_encode($manifest))->not->toContain('advanced_analytics')
        ->and(json_encode($manifest))->not->toContain('bulk_sms')
        ->and(json_encode($manifest))->not->toContain('storage/app/imports/inec/prepared/all');
});

test('checksums text is generated and included in release zip', function () {
    [$basePath, $config] = releaseBuildFixture();
    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $checksums = File::get($basePath.'/builds/checksums.txt');
    $zipChecksums = releaseBuildZipContents($result['zip_path'], 'checksums.txt');

    expect($checksums)->toContain('release-manifest.json')
        ->and($zipChecksums)->toContain('release-manifest.json')
        ->and($zipChecksums)->toContain('LICENSE.txt')
        ->and($zipChecksums)->toContain('EULA.txt');
});

test('manifest signing works when release key paths are configured', function () {
    [$basePath, $config] = releaseBuildFixture();

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
        'zip' => false,
    ]);

    $publicKey = File::get($basePath.'/storage/app/release-keys/campaign_release_public.pem');

    expect($result['manifest']['manifest_signature']['algorithm'])->toBe('rsa-sha256')
        ->and(app(\App\Services\ReleaseManifestSigner::class)->verify($result['manifest'], $publicKey))->toBeTrue()
        ->and(collect($result['warnings'])->pluck('label')->all())->toContain('Release signing keys');
});

test('unsigned local build warns but does not crash', function () {
    [$basePath, $config] = releaseBuildFixture();

    $result = releaseBuildService($basePath, $config)->dryRun([
        'profile' => 'full',
        'version' => '1.0.0',
    ]);

    expect(collect($result['warnings'])->pluck('label')->all())->toContain('Release manifest signing')
        ->and($result['manifest']['manifest_signature'])->toBeNull();
});

test('release build fails if keys are invalid', function () {
    [$basePath, $config] = releaseBuildFixture([], [
        'storage/app/release-keys/campaign_release_private.pem' => 'invalid-private-key',
        'storage/app/release-keys/campaign_release_public.pem' => 'invalid-public-key',
    ]);

    releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
        'zip' => false,
    ]);
})->throws(RuntimeException::class, 'Release signing keys are invalid');

test('private release key is excluded from generated zip', function () {
    [$basePath, $config] = releaseBuildFixture();

    app(\App\Services\ReleaseSigningKeyService::class, [
        'basePath' => $basePath,
        'signingConfig' => $config['signing'],
    ])->generate();

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->not->toContain('storage/app/release-keys/campaign_release_private.pem')
        ->and($names)->not->toContain('storage/app/release-keys/campaign_release_public.pem');
});

test('distribution watermark support is optional and included only when present', function () {
    [$basePath, $config] = releaseBuildFixture();
    $withoutWatermark = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds/without',
    ]);

    expect(releaseBuildZipNames($withoutWatermark['zip_path']))->not->toContain('storage/app/license/distribution.json');

    [$watermarkedBasePath, $watermarkedConfig] = releaseBuildFixture([], [
        'storage/app/license/distribution.json' => json_encode([
            'download_id' => 'DL-1',
            'order_number' => 'ORD-1',
            'license_key_hash' => hash('sha256', 'license'),
            'release_version' => 'v1.0.1',
            'package_type' => 'full',
            'deployment_type' => 'self_hosted',
            'generated_at' => now()->toIso8601String(),
        ]),
    ]);
    $withWatermark = releaseBuildService($watermarkedBasePath, $watermarkedConfig)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $watermarkedBasePath.'/builds/with',
    ]);

    expect(releaseBuildZipNames($withWatermark['zip_path']))->toContain('storage/app/license/distribution.json');
});

test('no paid encoder extension is required for release build service', function () {
    $composer = json_decode(File::get(base_path('composer.json')), true);
    $requires = array_keys(array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []));

    expect(implode(' ', $requires))->not->toContain('ioncube')
        ->and(implode(' ', $requires))->not->toContain('sourceguardian');
});

test('release build refuses when required csv is missing', function () {
    [$basePath, $config] = releaseBuildFixture(['storage/app/imports/campaign-locations/bootstrap/polling_units.csv']);

    releaseBuildService($basePath, $config)->dryRun([
        'profile' => 'full',
        'output' => $basePath.'/builds',
    ]);
})->throws(RuntimeException::class);

test('release build refuses when build manifest is missing', function () {
    [$basePath, $config] = releaseBuildFixture(['public/build/manifest.json']);

    releaseBuildService($basePath, $config)->dryRun([
        'profile' => 'full',
        'output' => $basePath.'/builds',
    ]);
})->throws(RuntimeException::class);

test('full profile excludes complete location data and includes bootstrap data', function () {
    [$basePath, $config] = releaseBuildFixture();

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->toContain('storage/app/imports/campaign-locations/bootstrap/polling_units.csv')
        ->and($names)->not->toContain('storage/app/imports/inec/prepared/all/polling_units.csv');
});

test('release package excludes full location csv folders even when present locally', function () {
    [$basePath, $config] = releaseBuildFixture([], [
        'storage/app/imports/inec/prepared/all/polling_units.csv' => 'full-data',
        'storage/app/imports/inec/prepared/all/states.csv' => 'full-data',
        'storage/app/imports/campaign-locations/prepared/all/polling_units.csv' => 'full-data',
    ]);

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->not->toContain('storage/app/imports/inec/prepared/all/polling_units.csv')
        ->and($names)->not->toContain('storage/app/imports/inec/prepared/all/states.csv')
        ->and($names)->not->toContain('storage/app/imports/campaign-locations/prepared/all/polling_units.csv')
        ->and($names)->toContain('storage/app/imports/campaign-locations/bootstrap/polling_units.csv');
});

test('release build excludes previous release zip artifacts', function () {
    [$basePath, $config] = releaseBuildFixture();
    $config['include_roots'][] = 'builds';
    $config['include_roots'][] = 'storage/app/private';
    $config['include_roots'][] = 'public/releases';

    releaseBuildPut($basePath, 'builds/releases/old-full.zip', 'old-release');
    releaseBuildPut($basePath, 'storage/app/private/campaign-manager/releases/old-full.zip', 'old-release');
    releaseBuildPut($basePath, 'storage/app/private/releases/archive/old-full.zip', 'old-release');
    releaseBuildPut($basePath, 'public/releases/old-full.zip', 'old-release');

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds/new',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->not->toContain('builds/releases/old-full.zip')
        ->and($names)->not->toContain('storage/app/private/campaign-manager/releases/old-full.zip')
        ->and($names)->not->toContain('storage/app/private/releases/archive/old-full.zip')
        ->and($names)->not->toContain('public/releases/old-full.zip');
});

test('release build skips excluded large folders', function () {
    [$basePath, $config] = releaseBuildFixture();
    $config['include_roots'][] = 'storage/app/imports/inec';
    $config['include_roots'][] = 'storage/logs';
    $config['include_roots'][] = 'node_modules';
    $config['include_roots'][] = 'vendor/.cache';
    $config['include_roots'][] = '.git';

    releaseBuildPut($basePath, 'storage/app/imports/inec/prepared/all/polling_units.csv', 'full-data');
    releaseBuildPut($basePath, 'storage/logs/laravel.log', 'log-data');
    releaseBuildPut($basePath, 'node_modules/example/index.js', 'node-module');
    releaseBuildPut($basePath, 'vendor/.cache/package/cache.bin', 'cache-data');
    releaseBuildPut($basePath, '.git/objects/test', 'git-data');

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->not->toContain('storage/app/imports/inec/prepared/all/polling_units.csv')
        ->and($names)->not->toContain('storage/logs/laravel.log')
        ->and($names)->not->toContain('node_modules/example/index.js')
        ->and($names)->not->toContain('vendor/.cache/package/cache.bin')
        ->and($names)->not->toContain('.git/objects/test');
});

test('checksum generation keeps normal large files disk backed', function () {
    [$basePath, $config] = releaseBuildFixture();
    releaseBuildPut($basePath, 'vendor/large-package.dat', str_repeat('x', 1024 * 1024));

    $builder = releaseBuildService($basePath, $config);
    $files = $builder->collectFiles('full', $config);

    $packageEntries = new ReflectionMethod(ReleaseBuildService::class, 'packageEntries');
    $packageEntries->setAccessible(true);
    $entries = $packageEntries->invoke($builder, $files['included'], $config);

    $largeEntry = collect($entries)->firstWhere('path', 'vendor/large-package.dat');

    expect($largeEntry)->not->toHaveKey('contents')
        ->and($largeEntry)->toHaveKey('source_path')
        ->and($largeEntry['size'])->toBe(1024 * 1024);

    $checksumList = new ReflectionMethod(ReleaseBuildService::class, 'checksumList');
    $checksumList->setAccessible(true);
    $checksums = $checksumList->invoke($builder, $entries);

    expect($checksums['vendor/large-package.dat'])->toBe(hash_file('sha256', $largeEntry['source_path']));
});

test('release build handles normal large files without loading package contents into result', function () {
    [$basePath, $config] = releaseBuildFixture();
    releaseBuildPut($basePath, 'vendor/normal-large-file.bin', str_repeat('L', 2 * 1024 * 1024));

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->toContain('vendor/normal-large-file.bin')
        ->and(collect($result['largest_included_files'])->pluck('path')->all())->toContain('vendor/normal-large-file.bin')
        ->and($result)->not->toHaveKey('package_files');
});

test('community profile includes only bootstrap location data', function () {
    [$basePath, $config] = releaseBuildFixture();

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'community',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);
    $manifest = json_decode(File::get($result['manifest_path']), true);

    expect($names)->toContain('storage/app/imports/campaign-locations/bootstrap/polling_units.csv')
        ->and($names)->not->toContain('storage/app/imports/inec/prepared/all/polling_units.csv')
        ->and($manifest['profile'])->toBe('community')
        ->and($manifest['includes_full_location_data'])->toBeFalse()
        ->and($manifest['includes_bootstrap_location_data'])->toBeTrue()
        ->and($manifest['automatic_location_provisioning'])->toBeFalse();
});

test('optional output path works', function () {
    [$basePath, $config] = releaseBuildFixture();

    $result = releaseBuildService($basePath, $config)->dryRun([
        'profile' => 'community',
        'version' => '1.0.0',
        'output' => 'public/releases',
    ]);

    expect($result['zip_path'])->toContain('public'.DIRECTORY_SEPARATOR.'releases');
});
