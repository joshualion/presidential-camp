<?php

namespace App\Console\Commands;

use App\Services\ReleaseBuildService;
use Illuminate\Console\Command;
use RuntimeException;

class CampaignBuildRelease extends Command
{
    protected $signature = 'campaign:build-release
        {profile : Release profile to build: full, community, or all}
        {--dry-run : Validate and show what would be packaged without writing a ZIP}
        {--force : Build the ZIP artifact}
        {--channel=stable : Release channel label}
        {--release-version= : Optional release version override}
        {--output= : Optional output directory}
        {--no-zip : Write release manifest/report only}';

    protected $description = 'Build a self-hosted Campaign Manager release ZIP from the master source tree.';

    public function handle(ReleaseBuildService $builder): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $noZip = (bool) $this->option('no-zip');
        $profile = strtolower((string) $this->argument('profile'));

        if (! $dryRun && ! $force && ! $noZip) {
            $this->error('Refusing to build without --dry-run, --force, or --no-zip.');

            return self::FAILURE;
        }

        if (!in_array($profile, ['full', 'community', 'all'], true)) {
            $this->error('Unsupported profile. Use full, community, or all.');

            return self::FAILURE;
        }

        if ($profile === 'all') {
            foreach (['full', 'community'] as $selectedProfile) {
                $status = $this->buildProfile($builder, $selectedProfile, $dryRun, $noZip);
                if ($status !== self::SUCCESS) {
                    return $status;
                }
                $this->newLine();
            }

            return self::SUCCESS;
        }

        return $this->buildProfile($builder, $profile, $dryRun, $noZip);
    }

    private function buildProfile(ReleaseBuildService $builder, string $profile, bool $dryRun, bool $noZip): int
    {
        try {
            $result = $builder->build([
                'profile' => $profile,
                'dry_run' => $dryRun,
                'zip' => ! $noZip,
                'channel' => $this->option('channel') ?: config('release.identity.channel'),
                'version' => $this->option('release-version') ?: config('release.identity.version'),
                'output' => $this->option('output') ?: base_path(config('release.default_output', 'builds/releases')),
            ]);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Campaign Manager self-hosted release build');
        $this->line('Profile: '.$result['profile']);
        $this->line('Version: '.$result['version']);
        $this->line('Channel: '.$result['channel']);
        $this->line('Mode: '.($dryRun ? 'dry-run' : ($noZip ? 'manifest-only' : 'zip')));
        $this->line('Output filename: '.$result['filename']);
        $this->line('Output path: '.$result['zip_path']);
        $this->newLine();

        $this->table(['Metric', 'Value'], [
            ['Files considered', (string) $result['total_files_considered']],
            ['Files included', (string) $result['included_count']],
            ['Files excluded', (string) $result['excluded_count']],
            ['Required directories', (string) count($result['included_directories'])],
            ['Estimated package size', $this->bytes((int) $result['estimated_size_bytes'])],
            ['Warnings', (string) count($result['warnings'])],
        ]);

        if ($result['excluded_categories'] !== []) {
            $this->newLine();
            $this->line('Top excluded categories:');
            foreach ($result['excluded_categories'] as $category => $count) {
                $this->line("- {$category}: {$count}");
            }
        }

        if ($result['warnings'] !== []) {
            $this->newLine();
            $this->warn('Warnings:');
            foreach ($result['warnings'] as $warning) {
                $this->line('- '.$warning['label'].': '.$warning['message']);
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->info('Dry-run complete. No ZIP was created.');

            return self::SUCCESS;
        }

        $this->line('Manifest/report: '.$result['manifest_path']);

        if ($noZip) {
            $this->info('Manifest-only build complete. No ZIP was created.');

            return self::SUCCESS;
        }

        $this->line('ZIP: '.$result['zip_path']);
        $this->line('ZIP size: '.$this->bytes((int) $result['size_bytes']));
        $this->line('SHA256: '.$result['checksum_sha256']);
        $this->info('Release ZIP build complete.');

        return self::SUCCESS;
    }

    private function bytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / 1024 / 1024 / 1024, 2).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
