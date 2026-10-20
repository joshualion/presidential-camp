<?php

namespace App\Services\Updates;

use App\Services\OfficialUpdateService;
use Illuminate\Support\Facades\File;

class AutomaticUpdateFlow
{
    public function __construct(
        private OfficialUpdateService $updates,
        private AutomaticUpdateService $automatic,
        private UpdateDownloadAccess $access,
    ) {}

    private function release(): array
    {
        $status = $this->updates->status();
        $checked = strtotime((string) ($status['checked_at'] ?? ''));
        if (! $checked || $checked < now()->subMinutes(15)->timestamp || empty($status['manifest'])) {
            $status = $this->updates->check();
        }
        if (empty($status['available']) || empty($status['verified']) || empty($status['manifest']) ||
            ! version_compare($status['latest_version'] ?? '0.0.0', $this->updates->installedVersion(), '>')) {
            throw new UpdateFlowException('release', 'No newer verified automatic update is available. Check for updates again.');
        }
        $this->updates->validateManifest($status['manifest'], $status['latest_version']);
        $policy = $status['manifest']['upgrade'] ?? [];
        if (($policy['protocol'] ?? null) !== 1 ||
            ! in_array($this->updates->installedVersion(), $policy['supported_sources'] ?? [], true)) {
            throw new UpdateFlowException('release', 'This release does not support automatic updating from your installed version. Use the official manual upgrade guide.');
        }

        foreach ($status['manifest']['requirements']['extensions'] ?? [] as $extension) {
            if (! extension_loaded($extension)) {
                throw new UpdateFlowException('server', 'This release needs the PHP extension '.$extension.'. Ask your hosting administrator to enable it before updating.');
            }
        }

        return $status;
    }

    public function review(): array
    {
        $status = $this->release();
        $quote = $this->access->quote($status);
        if (empty($quote['entitled']) && empty($quote['payment_available'])) {
            throw new UpdateFlowException('payment', 'Automatic-update payment is currently unavailable for this campaign scope. Try again later or use the free manual download.');
        }

        return ['status' => $status, 'entitlement' => $quote];
    }

    public function screen(): array
    {
        $result = ['status' => $this->updates->status(), 'entitlement' => null, 'issue' => null, 'prepared' => $this->prepared()];
        try {
            return array_merge($result, $this->review());
        } catch (UpdateFlowException $e) {
            $result['issue'] = ['reason' => $e->reason, 'message' => $e->getMessage()];
        } catch (UpdateException $e) {
            $result['issue'] = ['reason' => 'server', 'message' => $e->getMessage()];
        } catch (\Throwable) {
            $result['issue'] = ['reason' => 'service', 'message' => 'The update service could not complete this check. Try again. Your installation has not changed.'];
        }

        return $result;
    }

    public function prepared(): ?array
    {
        foreach (File::glob(storage_path('app/updates/staged/*/journal.json')) as $path) {
            $stage = json_decode(File::get($path), true);
            if (is_array($stage) && ($stage['state'] ?? null) === 'prepared') {
                return $stage;
            }
        }

        return null;
    }

    public function start(string $version, string $hash): array
    {
        $review = $this->review();
        $status = $review['status'];
        if ($status['latest_version'] !== $version || ! hash_equals($status['manifest']['checksum_sha256'], $hash)) {
            throw new UpdateFlowException('release', 'The available release changed. Review the current version before updating.');
        }
        if (empty($review['entitlement']['entitled'])) {
            throw new UpdateFlowException('coverage', 'Continue to secure payment before starting this automatic update. Nothing has been downloaded or installed.');
        }
        $stage = $this->prepared();
        if ($stage && (($stage['version'] ?? null) !== $version ||
            ($stage['manifest']['checksum_sha256'] ?? null) !== $hash)) {
            throw new UpdateFlowException('staged_package', 'An older package is still prepared. Remove that prepared package below, then retry this update.');
        }
        if (! $stage) {
            $stage = $this->updates->prepare(['version' => $version, 'sha256' => $hash]);
        }

        return $this->automatic->start($stage['id']);
    }
}
