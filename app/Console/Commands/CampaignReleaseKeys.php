<?php

namespace App\Console\Commands;

use App\Services\ReleaseSigningKeyService;
use Illuminate\Console\Command;
use RuntimeException;

class CampaignReleaseKeys extends Command
{
    protected $signature = 'campaign:release-keys
        {--generate : Generate release signing keys if they do not exist}
        {--status : Show release signing key status}
        {--verify : Sign and verify a test payload}
        {--force : Deliberately regenerate existing signing keys}';

    protected $description = 'Generate, validate, and verify Campaign Manager release signing keys.';

    public function handle(ReleaseSigningKeyService $keys): int
    {
        if (!$this->option('generate') && !$this->option('status') && !$this->option('verify')) {
            $this->error('Choose --generate, --status, or --verify.');

            return self::FAILURE;
        }

        try {
            if ($this->option('generate')) {
                if ($this->option('force')) {
                    $this->warn('WARNING: --force will replace the existing release signing key pair. Old signed manifests may no longer verify with the new public key.');
                }

                $result = $keys->generate((bool) $this->option('force'));
                $this->info($result['message']);
                $this->printStatus($result['status']);
            }

            if ($this->option('status')) {
                $this->printStatus($keys->status());
            }

            if ($this->option('verify')) {
                $result = $keys->verifyTestPayload();
                $result['ok'] ? $this->info($result['message']) : $this->error($result['message']);

                return $result['ok'] ? self::SUCCESS : self::FAILURE;
            }
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function printStatus(array $status): void
    {
        $this->line('Release signing keys');
        $this->line('Directory: '.$status['keys_dir']);
        $this->line('Private key: '.($status['private_exists'] ? 'present' : 'missing'));
        $this->line('Public key: '.($status['public_exists'] ? 'present' : 'missing'));
        $this->line('Private usable: '.($status['private_usable'] ? 'yes' : 'no'));
        $this->line('Public usable: '.($status['public_usable'] ? 'yes' : 'no'));
        $this->line('Pair matches: '.($status['pair_matches'] ? 'yes' : 'no'));
        $this->line('Valid: '.($status['valid'] ? 'yes' : 'no'));

        foreach ($status['errors'] as $error) {
            $this->warn($error);
        }
    }
}
