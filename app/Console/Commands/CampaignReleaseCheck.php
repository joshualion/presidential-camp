<?php

namespace App\Console\Commands;

use App\Services\ReleaseReadinessService;
use Illuminate\Console\Command;

class CampaignReleaseCheck extends Command
{
    protected $signature = 'campaign:release-check {--json : Output machine-readable JSON}';

    protected $description = 'Validate self-hosted release packaging readiness without building a ZIP.';

    public function handle(ReleaseReadinessService $readiness): int
    {
        $checks = $readiness->inspect();
        $summary = $readiness->summary($checks);

        if ($this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));

            return $readiness->hasCriticalFailures($checks) ? self::FAILURE : self::SUCCESS;
        }

        $this->line('Campaign Manager self-hosted release readiness');
        $this->line('Version: '.config('release.identity.version'));
        $this->line('Channel: '.config('release.identity.channel'));
        $this->newLine();

        $this->table(
            ['Status', 'Critical', 'Check', 'Message'],
            collect($checks)->map(fn (array $check) => [
                strtoupper($check['status']),
                $check['critical'] ? 'yes' : 'no',
                $check['label'],
                $check['message'],
            ])->all()
        );

        $this->newLine();
        $this->line("Passes: {$summary['passes']}  Warnings: {$summary['warnings']}  Failures: {$summary['failures']}");

        if ($readiness->hasCriticalFailures($checks)) {
            $this->error('Release readiness failed. Resolve critical failures before building a ZIP.');

            return self::FAILURE;
        }

        if ($summary['warnings'] > 0) {
            $this->warn('Release readiness passed with warnings. Review them before building a ZIP.');
        } else {
            $this->info('Release readiness passed.');
        }

        return self::SUCCESS;
    }
}
