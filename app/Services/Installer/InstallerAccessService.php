<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\File;
use RuntimeException;

/** A local server ownership check, independent of paid geography and activation. */
class InstallerAccessService
{
    public function path(): string
    {
        return storage_path('app/installer/access-token');
    }

    public function token(): string
    {
        if ($configured = config('campaign.installer_access_token')) {
            if (strlen($configured) < 32) {
                throw new RuntimeException('The configured server setup code must contain at least 32 characters.');
            }
            return $configured;
        }
        File::ensureDirectoryExists(dirname($this->path()), 0700);
        $lock = fopen($this->path().'.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Server setup ownership cannot be checked right now.');
        }
        try {
            chmod($this->path().'.lock', 0600);
            if (!File::exists($this->path())) {
                File::replace($this->path(), bin2hex(random_bytes(24)), 0600);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
        $token = trim(File::get($this->path()));
        if (strlen($token) < 32) {
            throw new RuntimeException('The server setup code file is incomplete. Restore it through your hosting control panel.');
        }
        return $token;
    }

    public function proof(): string
    {
        return hash('sha256', $this->token());
    }
}
