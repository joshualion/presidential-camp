<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    protected $systemSetting;

    public function __construct()
    {
        // Load the system setting once for reuse
        $this->systemSetting = SystemSetting::first();
    }

    /**
     * Store a file based on system settings (local or S3).
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path
     * @param string|null $oldFilePath
     * @return string|bool The stored file name or `false` on failure.
     */
    public function storeFile($file, $path, $oldFilePath = null)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString() . ($extension ? '.' . $extension : '');

        if ($this->systemSetting && $this->systemSetting->enable_s3_storage) {
            $this->configureS3();

            $s3Path = $path . '/' . $filename;
            $uploaded = Storage::disk('s3')->put($s3Path, file_get_contents($file));

            if ($uploaded) {
                if ($oldFilePath) {
                    $oldS3Path = $path . '/' . $oldFilePath;
                    if (Storage::disk('s3')->exists($oldS3Path)) {
                        Storage::disk('s3')->delete($oldS3Path);
                    }
                }
                return $filename;
            }
        }

        if ($this->systemSetting && $this->systemSetting->enable_local_storage) {
            return $this->storeLocally($file, $path, $filename, $oldFilePath);
        }

        // Fallback to local storage if system settings are missing or misconfigured.
        return $this->storeLocally($file, $path, $filename, $oldFilePath);
    }

    /**
     * Generate a file URL based on storage location.
     *
     * @param string $filename
     * @param string $path
     * @return string
     */
        // public function getFileUrl($filename, $path)
        // {
        //     if ($this->systemSetting->enable_s3_storage) {
        //         $this->configureS3();
        //         return Storage::disk('s3')->url($path . '/' . $filename);
        //     }

        //     return asset('uploads/' . $path . '/' . $filename);
        // }

    /**
     * Dynamically configure S3 settings.
     *
     * @return void
     */
    protected function configureS3()
    {
        if (!$this->systemSetting) {
            return;
        }

        config([
            'filesystems.disks.s3.key' => $this->systemSetting->s3_key,
            'filesystems.disks.s3.secret' => $this->systemSetting->s3_secret,
            'filesystems.disks.s3.region' => $this->systemSetting->s3_region,
            'filesystems.disks.s3.bucket' => $this->systemSetting->s3_bucket,
            'filesystems.disks.s3.endpoint' => $this->systemSetting->s3_endpoint,
        ]);
    }

    protected function hasS3Config(): bool
    {
        $setting = $this->systemSetting ?? SystemSetting::first();
        return $setting
            && !empty($setting->s3_key)
            && !empty($setting->s3_secret)
            && !empty($setting->s3_region)
            && !empty($setting->s3_bucket);
    }

    protected function storeLocally($file, string $path, string $filename, ?string $oldFilePath = null)
    {
        $targetDirectory = public_path('uploads/' . trim($path, '/'));

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $file->move($targetDirectory, $filename);

        if ($oldFilePath) {
            $oldFilePathFull = $targetDirectory . '/' . $oldFilePath;
            if (is_file($oldFilePathFull)) {
                @unlink($oldFilePathFull);
            }
        }

        return $filename;
    }

    public function getFileUrl(string $path): string
    {
        $systemSetting = $this->systemSetting ?? SystemSetting::first();

        $normalizedPath = ltrim($path, '/');
        $normalizedPath = Str::startsWith($normalizedPath, 'uploads/')
            ? substr($normalizedPath, strlen('uploads/'))
            : $normalizedPath;

        $localPath = public_path('uploads/' . $normalizedPath);

        // Prefer existing local file (covers legacy uploads even when S3 is turned on)
        if (is_file($localPath)) {
            return asset('uploads/' . $normalizedPath);
        }

        // If S3 is enabled OR we have S3 credentials (even if storing locally now), generate the S3 URL
        if ($this->hasS3Config()) {
            $this->configureS3();
            return Storage::disk('s3')->url($path);
        }

        // Fallback to local asset
        return asset('uploads/' . $normalizedPath);
    }

}
