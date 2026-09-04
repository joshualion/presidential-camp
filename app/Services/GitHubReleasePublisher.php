<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class GitHubReleasePublisher
{
    public function __construct(private ?array $releaseConfig = null)
    {
        $this->releaseConfig ??= config('release', []);
    }

    public function assertRepositoryAllowed(string $repository, bool $allowUnapproved = false): void
    {
        if ($allowUnapproved) {
            return;
        }

        $allowed = Arr::get($this->releaseConfig, 'github.allowed_repositories', []);

        if (! in_array($repository, $allowed, true)) {
            throw new RuntimeException("GitHub release repository [{$repository}] is not approved. Set CAMPAIGN_RELEASE_GITHUB_REPO to an approved public repository or use --allow-unapproved-repo deliberately.");
        }
    }

    public function publish(string $repository, string $tag, string $zipPath, string $title, ?string $notes = null, bool $allowUnapproved = false): array
    {
        $this->assertRepositoryAllowed($repository, $allowUnapproved);

        if (! File::isFile($zipPath)) {
            throw new RuntimeException('Release ZIP does not exist: '.$zipPath);
        }

        $command = [
            'gh',
            'release',
            'create',
            $tag,
            $zipPath.'#campaign-manager.zip',
            '--repo',
            $repository,
            '--title',
            $title,
            '--latest',
            '--verify-tag=false',
        ];

        if ($notes !== null && trim($notes) !== '') {
            $command[] = '--notes';
            $command[] = $notes;
        } else {
            $command[] = '--generate-notes';
        }

        $process = new Process($command);
        $process->setTimeout(null);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: trim($process->getOutput()) ?: 'GitHub release publishing failed.');
        }

        return [
            'repository' => $repository,
            'tag' => $tag,
            'asset' => 'campaign-manager.zip',
            'output' => trim($process->getOutput()),
        ];
    }
}
