<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ReleaseBuildService
{
    public function __construct(
        private ReleaseReadinessService $readiness,
        private ?string $basePath = null,
        private ?array $releaseConfig = null,
    ) {
        $this->basePath ??= base_path();
        $this->releaseConfig ??= config('release', []);
    }

    public function dryRun(array $options = []): array
    {
        return $this->build(array_merge($options, [
            'dry_run' => true,
            'zip' => false,
        ]));
    }

    public function build(array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $zipEnabled = (bool) ($options['zip'] ?? true);
        $profile = $this->profile((string) ($options['profile'] ?? 'full'));
        $version = $this->cleanSegment(ltrim(
            (string) ($options['version'] ?? Arr::get($this->releaseConfig, 'identity.version', '0.0.0')),
            'vV'
        ));
        $channel = $this->cleanSegment($options['channel'] ?? Arr::get($this->releaseConfig, 'identity.channel', 'self-hosted'));
        $effectiveConfig = $this->profileConfig($profile);
        $profileConfig = Arr::get($effectiveConfig, "profiles.{$profile}", []);
        $outputDirectory = $this->absolutePath($options['output'] ?? Arr::get($effectiveConfig, 'default_output', 'builds/releases'));
        $filename = str_replace('{version}', $version, Arr::get($profileConfig, 'filename', "campaign-manager-{$profile}-v{version}.zip"));
        $zipPath = $outputDirectory.DIRECTORY_SEPARATOR.$filename;

        $readiness = new ReleaseReadinessService($this->basePath, $effectiveConfig);
        $checks = $readiness->inspect();
        if ($readiness->hasCriticalFailures($checks)) {
            throw new RuntimeException('Release readiness has critical failures. Run php artisan campaign:release-check.');
        }

        $files = $this->collectFiles($profile, $effectiveConfig);
        $profileChecks = $this->profileChecks($profile, $effectiveConfig, $files);
        if ($profileChecks['failures'] !== []) {
            throw new RuntimeException(implode(' ', $profileChecks['failures']));
        }

        $packageFiles = $this->packageEntries($files['included'], $effectiveConfig);
        $checksums = $this->checksumList($packageFiles);
        $manifest = $this->manifest($version, $channel, $profile, $effectiveConfig, $files, $checks, $profileChecks, $packageFiles, $checksums);
        $signingWarnings = [];
        $signingConfig = array_merge(Arr::get($effectiveConfig, 'signing', []), ['_base_path' => $this->basePath]);

        if ($dryRun) {
            $keyStatus = (new ReleaseSigningKeyService($this->basePath, $signingConfig))->status();
            if ($keyStatus['valid']) {
                $signature = (new ReleaseManifestSigner())->sign($manifest, $signingConfig);
                $manifest = $signature['manifest'];
            } else {
                $signingWarnings[] = [
                    'label' => 'Release manifest signing',
                    'message' => 'Release manifest is unsigned in dry-run because release signing keys are not configured or valid.',
                ];
            }
        } else {
            $keyStatus = (new ReleaseSigningKeyService($this->basePath, $signingConfig))->ensureForBuild();
            $signature = (new ReleaseManifestSigner())->sign($manifest, $signingConfig);
            $manifest = $signature['manifest'];

            if ((new ReleaseManifestSigner())->verify($manifest, null, $signingConfig) !== true) {
                throw new RuntimeException('Release manifest signing verification failed. Release build stopped.');
            }

            if ($keyStatus['generated'] ?? false) {
                $signingWarnings[] = [
                    'label' => 'Release signing keys',
                    'message' => 'Release signing keys were generated automatically and existing keys were not overwritten.',
                ];
            }
        }
        $manifestJson = $this->json($manifest);
        $packageFiles[] = [
            'path' => 'release-manifest.json',
            'contents' => $manifestJson,
            'size' => strlen($manifestJson),
            'hardened' => false,
        ];
        $checksumsWithManifest = $this->checksumList($packageFiles);
        $checksumsText = $this->checksumsText($checksumsWithManifest);
        $packageFiles[] = [
            'path' => 'checksums.txt',
            'contents' => $checksumsText,
            'size' => strlen($checksumsText),
            'hardened' => false,
        ];
        $estimatedSize = array_sum(array_column($packageFiles, 'size'));
        $largestIncludedFiles = $this->largestFiles($packageFiles);

        $result = [
            'version' => $version,
            'channel' => $channel,
            'profile' => $profile,
            'filename' => $filename,
            'zip_path' => $zipPath,
            'manifest_path' => $this->manifestPath($zipPath, $zipEnabled, $version, $profileConfig),
            'release_notes_path' => $outputDirectory.DIRECTORY_SEPARATOR.'campaign-manager-v'.$version.'-notes.md',
            'dry_run' => $dryRun,
            'zip_enabled' => $zipEnabled,
            'total_files_considered' => $files['total_considered'],
            'included_count' => count($packageFiles),
            'excluded_count' => count($files['excluded']),
            'included_directories' => $this->requiredDirectoriesInPackage($effectiveConfig),
            'excluded_categories' => $this->excludedCategories($files['excluded']),
            'estimated_size_bytes' => $estimatedSize,
            'largest_included_files' => $largestIncludedFiles,
            'warnings' => array_merge($this->warnings($checks), $signingWarnings),
            'profile_warnings' => $profileChecks['warnings'],
            'manifest' => $manifest,
            'checksums' => $checksumsWithManifest,
            'checksum_sha256' => null,
            'size_bytes' => null,
        ];

        if ($dryRun) {
            return $result;
        }

        File::ensureDirectoryExists($outputDirectory);

        if ($zipEnabled) {
            $this->createZip($zipPath, $packageFiles, $effectiveConfig);
            $result['size_bytes'] = File::size($zipPath);
            $result['checksum_sha256'] = hash_file('sha256', $zipPath);
            $result['manifest']['checksum_sha256'] = $result['checksum_sha256'];
            $result['manifest']['size_bytes'] = $result['size_bytes'];
        }

        File::put($result['manifest_path'], $this->json($result['manifest']));
        File::put($outputDirectory.DIRECTORY_SEPARATOR.'checksums.txt', $checksumsText);
        File::put($result['release_notes_path'], $this->releaseNotesMarkdown($version, $result['manifest']['release_notes'] ?? []));

        return $result;
    }

    public function collectFiles(string $profile = 'full', ?array $effectiveConfig = null): array
    {
        $effectiveConfig ??= $this->profileConfig($profile);
        $included = [];
        $excluded = [];
        $seen = [];
        $total = 0;

        foreach ($this->includeRoots($profile, $effectiveConfig) as $root) {
            $path = $this->path($root);

            if (File::isFile($path)) {
                $total++;
                $this->pushFile($root, $path, $included, $excluded, $seen, $profile, $effectiveConfig);
                continue;
            }

            if (! File::isDirectory($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $total++;
                $relativePath = $this->relativePath($file->getPathname());
                $this->pushFile($relativePath, $file->getPathname(), $included, $excluded, $seen, $profile, $effectiveConfig);
            }
        }

        usort($included, fn (array $a, array $b) => $a['path'] <=> $b['path']);
        usort($excluded, fn (array $a, array $b) => $a['path'] <=> $b['path']);

        return [
            'included' => $included,
            'excluded' => $excluded,
            'total_considered' => $total,
        ];
    }

    private function pushFile(string $relativePath, string $absolutePath, array &$included, array &$excluded, array &$seen, string $profile, array $effectiveConfig): void
    {
        $relativePath = $this->normalize($relativePath);

        if (isset($seen[$relativePath])) {
            return;
        }

        $seen[$relativePath] = true;
        $excludedBy = $this->excludedBy($relativePath, $profile, $effectiveConfig);

        if ($excludedBy !== null) {
            $excluded[] = [
                'path' => $relativePath,
                'reason' => $excludedBy,
                'size' => File::size($absolutePath),
            ];

            return;
        }

        $included[] = [
            'path' => $relativePath,
            'absolute_path' => $absolutePath,
            'size' => File::size($absolutePath),
        ];
    }

    private function packageEntries(array $files, array $effectiveConfig): array
    {
        $entries = [];

        foreach ($files as $file) {
            $hardened = false;
            $entry = [
                'path' => $file['path'],
                'source_path' => $file['absolute_path'],
                'size' => $file['size'],
                'hardened' => false,
            ];

            if ($this->shouldHarden($file['path'], $effectiveConfig) && $this->canHarden($file, $effectiveConfig)) {
                $contents = $this->hardenPhp(File::get($file['absolute_path']));
                $hardened = true;
                $entry = [
                    'path' => $file['path'],
                    'contents' => $contents,
                    'size' => strlen($contents),
                    'hardened' => $hardened,
                ];
                unset($contents);
            }

            $entries[$file['path']] = $entry;
        }

        $distributionPath = 'storage/app/license/distribution.json';
        if (File::isFile($this->path($distributionPath)) && !isset($entries[$distributionPath])) {
            $entries[$distributionPath] = [
                'path' => $distributionPath,
                'source_path' => $this->path($distributionPath),
                'size' => File::size($this->path($distributionPath)),
                'hardened' => false,
            ];
        }

        ksort($entries);

        return array_values($entries);
    }

    private function entryHash(array $file): string
    {
        if (isset($file['source_path'])) {
            return hash_file('sha256', $file['source_path']);
        }

        return hash('sha256', $file['contents']);
    }

    private function checksumList(array $packageFiles): array
    {
        $checksums = [];

        foreach ($packageFiles as $file) {
            if ($file['path'] === 'checksums.txt') {
                continue;
            }

            $checksums[$file['path']] = $this->entryHash($file);
        }

        ksort($checksums);

        return $checksums;
    }

    private function checksumsText(array $checksums): string
    {
        return collect($checksums)
            ->map(fn (string $hash, string $path) => $hash.'  '.$path)
            ->implode(PHP_EOL).PHP_EOL;
    }

    private function protectedFiles(array $packageFiles, array $effectiveConfig): array
    {
        $protected = [];

        foreach ($packageFiles as $file) {
            if ($this->matchesAny($file['path'], Arr::get($effectiveConfig, 'protected_file_patterns', []))) {
                $protected[$file['path']] = $this->entryHash($file);
            }
        }

        ksort($protected);

        return $protected;
    }

    private function shouldHarden(string $path, array $effectiveConfig): bool
    {
        return str_ends_with($path, '.php')
            && $this->matchesAny($path, Arr::get($effectiveConfig, 'harden_php_patterns', []));
    }

    private function canHarden(array $file, array $effectiveConfig): bool
    {
        $maxBytes = (int) Arr::get($effectiveConfig, 'harden_php_max_bytes', 262144);

        return (int) ($file['size'] ?? 0) > 0
            && (int) $file['size'] <= $maxBytes;
    }

    private function matchesAny(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->matches($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function hardenPhp(string $contents): string
    {
        $tokens = token_get_all($contents);
        $output = '';

        foreach ($tokens as $token) {
            if (is_string($token)) {
                $output .= $token;
                continue;
            }

            [$id, $text] = $token;

            if (in_array($id, [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($id === T_WHITESPACE) {
                if ($output !== '' && !str_ends_with($output, ' ')) {
                    $output .= ' ';
                }
                continue;
            }

            $output .= $text;
        }

        return trim($output).PHP_EOL;
    }

    private function largestFiles(array $packageFiles, int $limit = 10): array
    {
        $files = array_map(
            fn (array $file) => [
                'path' => $file['path'],
                'size' => (int) ($file['size'] ?? 0),
                'hardened' => (bool) ($file['hardened'] ?? false),
            ],
            $packageFiles
        );

        usort($files, fn (array $a, array $b) => $b['size'] <=> $a['size']);

        return array_slice($files, 0, $limit);
    }

    private function createZip(string $zipPath, array $files, array $effectiveConfig): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension is required to build release ZIP files.');
        }

        $zip = new ZipArchive();
        $temporaryZipPath = $zipPath.'.tmp';

        File::delete($temporaryZipPath);

        if ($zip->open($temporaryZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create release ZIP at '.$zipPath);
        }

        foreach ($this->requiredDirectoriesInPackage($effectiveConfig) as $directory) {
            $zip->addEmptyDir($directory);
        }

        foreach ($files as $file) {
            if (isset($file['source_path'])) {
                $added = $zip->addFile($file['source_path'], $file['path']);
            } else {
                $added = $zip->addFromString($file['path'], $file['contents']);
            }

            if ($added !== true) {
                $zip->close();
                File::delete($temporaryZipPath);

                throw new RuntimeException('Unable to add release file to ZIP: '.$file['path']);
            }

            $zip->setCompressionName($file['path'], ZipArchive::CM_STORE);
        }
        $zip->close();

        if (! File::isFile($temporaryZipPath)) {
            throw new RuntimeException('Unable to create release ZIP at '.$zipPath);
        }

        File::delete($zipPath);
        File::move($temporaryZipPath, $zipPath);
    }

    private function manifest(string $version, string $channel, string $profile, array $effectiveConfig, array $files, array $checks, array $profileChecks, array $packageFiles, array $checksums): array
    {
        $profileConfig = Arr::get($effectiveConfig, "profiles.{$profile}", []);
        $protectedFiles = $this->protectedFiles($packageFiles, $effectiveConfig);

        return [
            'product' => 'Campaign Manager',
            'app_name' => Arr::get($effectiveConfig, 'identity.name', 'Campaign Manager Core'),
            'package' => Arr::get($effectiveConfig, 'identity.package', 'campaign-manager-core'),
            'package_type' => 'universal_core',
            'version' => $version,
            'profile' => $profile,
            'channel' => $channel,
            'build_date' => now()->toIso8601String(),
            'deployment_type' => 'self_hosted',
            'bundled_location_data' => false,
            'portal_location_provisioning' => true,
            'minimum_php' => Arr::get($effectiveConfig, 'identity.minimum_php', '8.2.0'),
            'includes_full_location_data' => (bool) Arr::get($profileConfig, 'includes_full_location_data', false),
            'includes_bootstrap_location_data' => (bool) Arr::get($profileConfig, 'includes_bootstrap_location_data', false),
            'includes_vendor' => File::isFile($this->path('vendor/autoload.php')),
            'includes_built_assets' => File::isFile($this->path('public/build/manifest.json')),
            'deployment_mode' => 'self_hosted',
            'licensed_features_controlled_by_activation' => false,
            'automatic_location_provisioning' => (bool) Arr::get($profileConfig, 'automatic_location_provisioning', false),
            'requirements' => [
                'php' => '^8.2',
                'laravel' => '^11.9',
                'composer_runtime' => 'vendor is bundled; customers should not need Composer for normal install.',
                'frontend_runtime' => 'public/build is bundled; customers should not need Node.js, npm, or Vite for normal install.',
            ],
            'features' => [
                'browser_installer' => 'included',
                'license_activation' => 'not_required_for_core_operation',
                'location_setup' => 'manual_or_quickstart_geography_provisioning',
                'community_forum' => $profile === 'community' ? 'included_as_bootstrap_release' : 'optional_disabled_by_default',
                'email_notifications' => 'included_with_jurisdiction_targeting_and_delivery_reports',
            ],
            'release_notes' => $this->configuredReleaseNotes($effectiveConfig, $version),
            'counts' => [
                'files_considered' => $files['total_considered'],
                'files_included' => count($files['included']),
                'files_excluded' => count($files['excluded']),
            ],
            'location_data' => [
                'status' => $profileChecks['location_data_ready'] ? 'ready' : 'incomplete',
                'profile' => $profile,
                'bundled_full_data' => false,
                'portal_provisioning' => true,
                'record_counts' => $profileChecks['location_counts'],
            ],
            'protected_files' => $protectedFiles,
            'checksums' => $checksums,
            'update_entitlement_warning' => 'Updates, support, and portal entitlements may be blocked or flagged if protected files are modified, the release manifest is missing, or the manifest signature is invalid.',
            'distribution_watermark_present' => File::isFile($this->path('storage/app/license/distribution.json')),
            'built_assets' => [
                'public/build/manifest.json' => File::isFile($this->path('public/build/manifest.json')),
            ],
            'required_post_upload_steps' => [
                'Point the domain document root to the public directory.',
                'Copy .env.example to .env and enter database/app URL values.',
                'Open the browser installer.',
                'Choose manual geography configuration or optional QuickStart Geography Provisioning.',
                'Create the first superadmin.',
            ],
            'excluded_local_files_summary' => $this->customerExcludedSummary($files['excluded']),
            'warnings' => $this->warnings($checks),
            'manifest_signature' => null,
            'checksum_sha256' => null,
            'size_bytes' => null,
        ];
    }

    private function warnings(array $checks): array
    {
        return collect($checks)
            ->where('status', 'warning')
            ->map(fn (array $check) => [
                'label' => $check['label'],
                'message' => $check['message'],
            ])
            ->values()
            ->all();
    }

    private function profile(string $profile): string
    {
        $profile = strtolower(trim($profile));

        if (!in_array($profile, ['standard', 'full', 'community'], true)) {
            throw new RuntimeException('Unsupported release profile. Use standard, full, community, or all.');
        }

        return $profile;
    }

    private function profileConfig(string $profile): array
    {
        $config = $this->releaseConfig;
        $profileConfig = Arr::get($config, "profiles.{$profile}");

        if (!$profileConfig) {
            throw new RuntimeException("Release profile [{$profile}] is not configured.");
        }

        $config['required_location_csvs'] = Arr::get($profileConfig, 'required_location_csvs', []);

        return $config;
    }

    private function profileChecks(string $profile, array $effectiveConfig, array $files): array
    {
        $profileConfig = Arr::get($effectiveConfig, "profiles.{$profile}", []);
        $requiredCsvs = Arr::get($profileConfig, 'required_location_csvs', []);
        $failures = [];
        $warnings = [];

        foreach ($requiredCsvs as $csv) {
            if (!File::isFile($this->path($csv))) {
                $failures[] = 'The selected release profile is missing its location data package.';
                break;
            }
        }

        $includedPaths = collect($files['included'])->pluck('path')->all();
        $excludedRequiredFiles = collect(Arr::get($effectiveConfig, 'required_files', []))
            ->reject(fn (string $path) => in_array($this->normalize($path), $includedPaths, true))
            ->values()
            ->all();

        if ($excludedRequiredFiles !== []) {
            $failures[] = 'Required release files are excluded from the selected profile: '.implode(', ', $excludedRequiredFiles).'.';
        }

        if ($profile === 'community') {
            $fullDataIncluded = collect($includedPaths)->contains(fn (string $path) => str_starts_with($path, 'storage/app/imports/inec/prepared/all/')
                || str_starts_with($path, 'storage/app/imports/campaign-locations/prepared/all/'));
            if ($fullDataIncluded) {
                $failures[] = 'Community release cannot include the complete location data package.';
            }
        }

        $counts = $this->locationRecordCounts('storage/app/imports/campaign-locations/bootstrap');

        return [
            'failures' => array_values(array_unique($failures)),
            'warnings' => $warnings,
            'location_data_ready' => $failures === [],
            'location_counts' => $counts,
        ];
    }

    private function locationRecordCounts(string $sourceRoot): array
    {
        $files = [
            'states' => 'states.csv',
            'senatorial_districts' => 'senatorial_districts.csv',
            'federal_constituencies' => 'federal_constituencies.csv',
            'lgas' => 'local_government_areas.csv',
            'wards' => 'wards.csv',
            'polling_units' => 'polling_units.csv',
        ];

        $counts = [];
        $regions = [];

        foreach ($files as $key => $file) {
            $path = $this->path($sourceRoot.'/'.$file);
            $counts[$key] = File::isFile($path) ? $this->countCsvRecords($path) : 0;

            if ($key === 'states' && File::isFile($path)) {
                $lineNumber = 0;
                foreach (File::lines($path) as $line) {
                    if ($lineNumber++ === 0) {
                        continue;
                    }
                    $columns = str_getcsv($line);
                    if (($columns[1] ?? null) !== null) {
                        $regions[trim((string) $columns[1])] = true;
                    }
                }
            }
        }

        return array_merge(['regions' => count($regions)], $counts);
    }

    private function countCsvRecords(string $path): int
    {
        $count = 0;
        foreach (File::lines($path) as $line) {
            if (trim((string) $line) !== '') {
                $count++;
            }
        }

        return max(0, $count - 1);
    }

    private function customerExcludedSummary(array $excluded): array
    {
        return collect($this->excludedCategories($excluded))
            ->only(['.env', '.git/.github', 'node_modules', 'logs/cache/sessions', 'uploads', 'installed.lock', 'old builds'])
            ->all();
    }

    private function excludedCategories(array $excluded): array
    {
        $categories = [
            '.env' => 0,
            '.git/.github' => 0,
            'node_modules' => 0,
            'logs/cache/sessions' => 0,
            'uploads' => 0,
            'installed.lock' => 0,
            'old builds' => 0,
            'test artifacts' => 0,
            'other' => 0,
        ];

        foreach ($excluded as $file) {
            $path = $file['path'];
            $category = match (true) {
                str_starts_with($path, '.env') => '.env',
                str_starts_with($path, '.git') || str_starts_with($path, '.github') => '.git/.github',
                str_starts_with($path, 'node_modules/') => 'node_modules',
                str_starts_with($path, 'storage/logs/')
                    || str_starts_with($path, 'storage/framework/cache/')
                    || str_starts_with($path, 'storage/framework/sessions/')
                    || str_starts_with($path, 'storage/framework/views/')
                    || str_starts_with($path, 'bootstrap/cache/') => 'logs/cache/sessions',
                str_starts_with($path, 'public/uploads/') || str_starts_with($path, 'storage/app/public/uploads/') => 'uploads',
                $path === 'storage/app/installed.lock' => 'installed.lock',
                str_starts_with($path, 'builds/') || str_starts_with($path, 'storage/app/releases/') || str_starts_with($path, 'public/releases/') || str_ends_with($path, '.zip') => 'old builds',
                str_starts_with($path, 'tests/') || str_contains($path, 'phpunit') || str_contains($path, 'pest') => 'test artifacts',
                default => 'other',
            };
            $categories[$category]++;
        }

        return array_filter($categories, fn (int $count) => $count > 0);
    }

    private function excludedBy(string $relativePath, string $profile, array $effectiveConfig): ?string
    {
        $excludedBy = null;

        foreach ($this->excludePatterns($profile, $effectiveConfig) as $pattern) {
            $negated = str_starts_with($pattern, '!');
            $cleanPattern = $negated ? substr($pattern, 1) : $pattern;

            if (! $this->matches($cleanPattern, $relativePath)) {
                continue;
            }

            $excludedBy = $negated ? null : $pattern;
        }

        return $excludedBy;
    }

    private function matches(string $pattern, string $relativePath): bool
    {
        $pattern = $this->normalize($pattern);
        $relativePath = $this->normalize($relativePath);

        if (Str::is($pattern, $relativePath) || Str::is($pattern, basename($relativePath))) {
            return true;
        }

        if (! str_contains($pattern, '*')) {
            return $relativePath === $pattern || str_starts_with($relativePath, $pattern.'/');
        }

        return false;
    }

    private function includeRoots(string $profile, array $effectiveConfig): array
    {
        return array_values(array_unique(array_merge(
            Arr::get($effectiveConfig, 'include_roots', []),
            Arr::get($effectiveConfig, "profiles.{$profile}.location_data_roots", [])
        )));
    }

    private function excludePatterns(string $profile, array $effectiveConfig): array
    {
        $patterns = Arr::get($effectiveConfig, 'exclude_patterns', []);

        $patterns[] = 'storage/app/imports/inec/prepared/all/*';
        $patterns[] = 'storage/app/imports/inec/prepared/all/**';
        $patterns[] = 'storage/app/imports/campaign-locations/prepared/all/*';
        $patterns[] = 'storage/app/imports/campaign-locations/prepared/all/**';
        $patterns[] = 'storage/app/imports/*/prepared/all/*';
        $patterns[] = 'storage/app/imports/*/prepared/all/**';
        $patterns[] = 'storage/app/imports/*/full/*';
        $patterns[] = 'storage/app/imports/*/full-data/*';
        $patterns[] = 'storage/app/private/campaign-manager/releases/*.zip';
        $patterns[] = 'storage/app/private/releases/archive/*.zip';
        $patterns[] = 'storage/app/private/*';
        $patterns[] = 'storage/logs/*';
        $patterns[] = 'storage/logs/**';
        $patterns[] = '!storage/logs/.gitkeep';
        $patterns[] = 'node_modules/*';
        $patterns[] = 'node_modules/**';
        $patterns[] = 'vendor/.cache/*';
        $patterns[] = 'vendor/.cache/**';
        $patterns[] = '.git/*';
        $patterns[] = '.git/**';
        $patterns[] = '*.zip';

        return $patterns;
    }

    private function requiredDirectoriesInPackage(array $effectiveConfig): array
    {
        return array_values(array_unique(array_map(
            fn (string $directory) => $this->normalize($directory),
            Arr::get($effectiveConfig, 'writable_directories', [])
        )));
    }

    private function manifestPath(string $zipPath, bool $zipEnabled, string $version, array $profileConfig): string
    {
        if (! $zipEnabled) {
            return dirname($zipPath).DIRECTORY_SEPARATOR.str_replace('{version}', $version, Arr::get($profileConfig, 'manifest_filename', 'release-manifest.json'));
        }

        return dirname($zipPath).DIRECTORY_SEPARATOR.str_replace('{version}', $version, Arr::get($profileConfig, 'manifest_filename', basename($zipPath, '.zip').'.json'));
    }

    private function writeChecksums(string $outputDirectory, array $result): void
    {
        if (empty($result['checksum_sha256'])) {
            return;
        }

        $path = $outputDirectory.DIRECTORY_SEPARATOR.'checksums.txt';
        $line = $result['checksum_sha256'].'  '.$result['filename'];
        $lines = File::isFile($path)
            ? collect(File::lines($path))->reject(fn (string $existing) => str_ends_with(trim($existing), '  '.$result['filename']))->values()->all()
            : [];

        $lines[] = $line;

        File::put($path, implode(PHP_EOL, $lines).PHP_EOL);
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\/\\\\]/', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        return $this->path($path);
    }

    private function path(string $relativePath): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }

    private function relativePath(string $absolutePath): string
    {
        return $this->normalize(Str::after($absolutePath, rtrim($this->basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR));
    }

    private function normalize(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    private function cleanSegment(string $segment): string
    {
        return preg_replace('/[^A-Za-z0-9._-]+/', '-', $segment) ?: 'release';
    }

    private function configuredReleaseNotes(array $effectiveConfig, string $version): array
    {
        $notes = Arr::get($effectiveConfig, 'release_notes', []);

        return array_values($notes[ltrim($version, 'vV')] ?? []);
    }

    private function releaseNotesMarkdown(string $version, array $notes): string
    {
        $body = '# Campaign Manager v'.$version.PHP_EOL.PHP_EOL;

        if ($notes === []) {
            return $body.'No release notes were configured for this release.'.PHP_EOL;
        }

        $body .= '## Fixed'.PHP_EOL;
        foreach ($notes as $line) {
            $body .= '- '.trim((string) $line).PHP_EOL;
        }

        return $body;
    }

    private function json(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
}
