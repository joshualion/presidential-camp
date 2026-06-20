<?php

namespace App\Services\Installer;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Throwable;

class StorageSetupService
{
    public function inspectAndLink(): array
    {
        File::ensureDirectoryExists(storage_path('app/public'));

        $publicLink = public_path('storage');
        if (File::exists($publicLink)) {
            return ['status' => 'pass', 'message' => 'Public storage link already exists.'];
        }

        try {
            Artisan::call('storage:link');
            return File::exists($publicLink)
                ? ['status' => 'pass', 'message' => 'Public storage link created.']
                : ['status' => 'warning', 'message' => 'Storage link command ran, but the link was not detected. Create public/storage manually if uploads are unavailable.'];
        } catch (Throwable $e) {
            return ['status' => 'warning', 'message' => 'Could not create public storage symlink automatically. On shared hosting, create public/storage pointing to storage/app/public manually.'];
        }
    }
}
