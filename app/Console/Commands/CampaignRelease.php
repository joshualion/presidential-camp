<?php

namespace App\Console\Commands;

use App\Services\GitHubReleasePublisher;
use App\Services\ReleaseBuildService;
use App\Services\ReleaseVersionState;
use Illuminate\Console\Command;
use RuntimeException;

class CampaignRelease extends Command
{
    protected $signature = 'campaign:release
        {--build-only : Build campaign-manager.zip locally without offering GitHub publishing}
        {--release-version= : Explicit version to build, for example v1.1.0}
        {--version-bump= : Non-interactive version bump: patch, minor, or major}
        {--channel=stable : Release channel label}
        {--output= : Optional output directory}
        {--repo= : Explicit public GitHub repository owner/name}
        {--allow-unapproved-repo : Deliberately allow a repository outside the approved release allow-list}';

    protected $description = 'Build the standard Campaign Manager release ZIP and optionally publish it to the approved public GitHub Releases repository.';

    public function handle(ReleaseBuildService $builder, GitHubReleasePublisher $publisher, ReleaseVersionState $versionState): int
    {
        try {
            $currentVersion = $versionState->current();
            $version = $this->option('release-version')
                ? $this->normalizeVersion((string) $this->option('release-version'))
                : $this->nextVersion($currentVersion);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $repository = trim((string) ($this->option('repo') ?: config('release.github.repository', 'joshualion/Campaign-Manager')));
        $allowUnapproved = (bool) $this->option('allow-unapproved-repo');

        $this->line('Campaign Manager release');
        $this->line('Current version: v'.$currentVersion);
        $this->line('New version: v'.$version);
        $this->line('Package: campaign-manager.zip');
        $this->line('GitHub release repository: '.$repository);

        try {
            $publisher->assertRepositoryAllowed($repository, $allowUnapproved);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        try {
            $result = $builder->build([
                'profile' => 'standard',
                'dry_run' => false,
                'zip' => true,
                'channel' => $this->option('channel') ?: config('release.identity.channel'),
                'version' => $version,
                'output' => $this->option('output') ?: base_path(config('release.default_output', 'builds/releases')),
            ]);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line('ZIP: '.$result['zip_path']);
        $this->line('Manifest/report: '.$result['manifest_path']);
        $this->line('SHA256: '.$result['checksum_sha256']);
        $this->line('Vendor bundled: '.($result['manifest']['includes_vendor'] ? 'yes' : 'no'));
        $this->line('Compiled assets bundled: '.($result['manifest']['includes_built_assets'] ? 'yes' : 'no'));

        if ($this->option('build-only')) {
            $this->info('Build-only complete. No GitHub release was published.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Publish v'.$version.' to '.$repository.' using GitHub CLI?', false)) {
            $this->info('Release package built locally. GitHub publishing was skipped.');

            return self::SUCCESS;
        }

        try {
            $published = $publisher->publish(
                $repository,
                'v'.$version,
                $result['zip_path'],
                'Campaign Manager v'.$version,
                $this->releaseNotes($version),
                $allowUnapproved
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $versionState->persist($version, $published['repository'], $published['tag'], $published['asset']);

        $this->info('Published GitHub Release '.$published['tag'].' to '.$published['repository'].' with asset campaign-manager.zip.');

        return self::SUCCESS;
    }

    private function nextVersion(string $currentVersion): string
    {
        $bump = strtolower(trim((string) ($this->option('version-bump') ?: '')));

        if ($bump === '') {
            $bump = $this->input->isInteractive()
                ? strtolower((string) $this->choice('Select version bump', ['patch', 'minor', 'major'], 'patch'))
                : 'patch';
        }

        if (! in_array($bump, ['patch', 'minor', 'major'], true)) {
            throw new RuntimeException('Unsupported version bump. Use patch, minor, or major.');
        }

        [$major, $minor, $patch] = $this->versionParts($currentVersion);

        return match ($bump) {
            'major' => ($major + 1).'.0.0',
            'minor' => $major.'.'.($minor + 1).'.0',
            default => $major.'.'.$minor.'.'.($patch + 1),
        };
    }

    private function normalizeVersion(string $version): string
    {
        return implode('.', $this->versionParts(ltrim(trim($version), 'vV')));
    }

    private function versionParts(string $version): array
    {
        if (! preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
            throw new RuntimeException('Release version must use semantic version format, for example v1.0.6.');
        }

        return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
    }

    private function releaseNotes(string $version): ?string
    {
        $notes = config('release.release_notes.'.$version, []);

        if ($notes === []) {
            return null;
        }

        return collect($notes)
            ->map(fn (string $line) => '- '.$line)
            ->implode(PHP_EOL);
    }
}
