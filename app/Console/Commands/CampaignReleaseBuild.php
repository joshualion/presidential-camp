<?php

namespace App\Console\Commands;

use App\Services\ReleaseBuildService;
use Illuminate\Console\Command;
use RuntimeException;

class CampaignReleaseBuild extends Command
{
    protected $signature = 'campaign:release-build
        {--type=full : Release type: full, community-bootstrap, or all}
        {--release-version= : Release version override, for example v1.0.3}
        {--dry-run : Validate and show what would be packaged without writing a ZIP}
        {--force : Build the ZIP artifact}
        {--channel=stable : Release channel label}
        {--output= : Optional output directory}
        {--no-zip : Write release manifest/report only}';

    protected $description = 'Build a self-hosted Campaign Manager release ZIP using type-based release options.';

    public function handle(ReleaseBuildService $builder): int
    {
        $type = strtolower((string) $this->option('type'));
        $profile = match ($type) {
            'full' => 'full',
            'community-bootstrap', 'community' => 'community',
            'all' => 'all',
            default => null,
        };

        if (!$profile) {
            $this->error('Unsupported release type. Use full, community-bootstrap, or all.');

            return self::FAILURE;
        }

        if (!$this->option('dry-run') && !$this->option('force') && !$this->option('no-zip')) {
            $this->error('Refusing to build without --dry-run, --force, or --no-zip.');

            return self::FAILURE;
        }

        $profiles = $profile === 'all' ? ['full', 'community'] : [$profile];

        foreach ($profiles as $selectedProfile) {
            try {
                $result = $builder->build([
                    'profile' => $selectedProfile,
                    'dry_run' => (bool) $this->option('dry-run'),
                    'zip' => ! $this->option('no-zip'),
                    'channel' => $this->option('channel') ?: config('release.identity.channel'),
                    'version' => $this->option('release-version') ?: config('release.identity.version'),
                    'output' => $this->option('output') ?: base_path(config('release.default_output', 'builds/releases')),
                ]);
            } catch (RuntimeException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->line('Campaign Manager release build');
            $this->line('Type: '.$type);
            $this->line('Profile: '.$result['profile']);
            $this->line('Version: '.$result['version']);
            $this->line('Output: '.$result['zip_path']);
            $this->line('Manifest: '.$result['manifest_path']);
            $this->line('Warnings: '.count($result['warnings']));

            if (! empty($result['largest_included_files'])) {
                $this->line('Largest packaged files:');

                foreach (array_slice($result['largest_included_files'], 0, 5) as $file) {
                    $this->line(sprintf(
                        '- %s (%s bytes%s)',
                        $file['path'],
                        number_format((int) $file['size']),
                        $file['hardened'] ? ', hardened copy' : ''
                    ));
                }
            }

            foreach ($result['warnings'] as $warning) {
                $this->warn('- '.$warning['label'].': '.$warning['message']);
            }

            if ($this->option('dry-run')) {
                $this->info('Dry-run complete. No ZIP was created.');
            } elseif ($this->option('no-zip')) {
                $this->info('Manifest-only build complete. No ZIP was created.');
            } else {
                $this->info('Release ZIP build complete.');
            }
        }

        return self::SUCCESS;
    }
}
