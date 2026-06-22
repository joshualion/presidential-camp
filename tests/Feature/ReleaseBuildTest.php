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

test('release build includes required csv build manifest docs and vendor autoload', function () {
    [$basePath, $config] = releaseBuildFixture();
    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'channel' => 'stable',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->toContain('storage/app/imports/inec/prepared/all/polling_units.csv')
        ->and($names)->toContain('public/build/manifest.json')
        ->and($names)->toContain('docs/self-hosted-installation-guide.md')
        ->and($names)->toContain('vendor/autoload.php')
        ->and($names)->toContain('README-SELF-HOSTED.md')
        ->and($names)->toContain('storage/logs/.gitkeep')
        ->and($names)->not->toContain('docs/pre-release-cleanup-checklist.md');
});

test('manifest contains version channel and checksum fields', function () {
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
        ->and($manifest['includes_full_location_data'])->toBeTrue()
        ->and($manifest['includes_bootstrap_location_data'])->toBeFalse()
        ->and($manifest)->toHaveKey('location_data')
        ->and(json_encode($manifest))->not->toContain('advanced_analytics')
        ->and(json_encode($manifest))->not->toContain('bulk_sms')
        ->and(json_encode($manifest))->not->toContain('storage/app/imports/inec/prepared/all');
});

test('release build refuses when required csv is missing', function () {
    [$basePath, $config] = releaseBuildFixture(['storage/app/imports/inec/prepared/all/polling_units.csv']);

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

test('full profile includes complete location data and excludes bootstrap data', function () {
    [$basePath, $config] = releaseBuildFixture();

    $result = releaseBuildService($basePath, $config)->build([
        'profile' => 'full',
        'version' => '1.0.0',
        'output' => $basePath.'/builds',
    ]);

    $names = releaseBuildZipNames($result['zip_path']);

    expect($names)->toContain('storage/app/imports/inec/prepared/all/polling_units.csv')
        ->and($names)->not->toContain('storage/app/imports/campaign-locations/bootstrap/polling_units.csv');
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
