<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

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
        $filename = date('YmdHi') . $file->getClientOriginalName();

        if ($this->systemSetting->enable_s3_storage) {
            // Ensure dynamic S3 configuration
            $this->configureS3();

            // Upload to S3
            $s3Path = $path . '/' . $filename;
            $uploaded = Storage::disk('s3')->put($s3Path, file_get_contents($file));
            // Use a file stream
               // $uploaded = Storage::disk('s3')->put($s3Path, fopen($file->getRealPath(), 'r'));

            if ($uploaded) {
                // Delete old file from S3, if provided
                if ($oldFilePath) {
                    $oldS3Path = $path . '/' . $oldFilePath;
                    if (Storage::disk('s3')->exists($oldS3Path)) {
                        Storage::disk('s3')->delete($oldS3Path);
                    }
                }
                return $filename;
            }
        } elseif ($this->systemSetting->enable_local_storage) {
            // Upload to local storage
            $file->move(public_path('uploads/' . $path), $filename);

            // Delete old file locally, if provided
            if ($oldFilePath) {
                $oldFilePathFull = public_path('uploads/' . $path . '/' . $oldFilePath);
                if (file_exists($oldFilePathFull)) {
                    unlink($oldFilePathFull);
                }
            }
            return $filename;
        }

        return false; // Return false if neither storage is enabled
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
        config([
            'filesystems.disks.s3.key' => $this->systemSetting->s3_key,
            'filesystems.disks.s3.secret' => $this->systemSetting->s3_secret,
            'filesystems.disks.s3.region' => $this->systemSetting->s3_region,
            'filesystems.disks.s3.bucket' => $this->systemSetting->s3_bucket,
            'filesystems.disks.s3.endpoint' => $this->systemSetting->s3_endpoint,
        ]);
    }


    public function getFileUrl(string $path): string
    {
        $SystemSetting = SystemSetting::first();
    
        if ($SystemSetting->enable_s3_storage) {
            return Storage::disk('s3')->url($path);
        } elseif ($SystemSetting->enable_local_storage) {
            return asset('uploads/' . $path);
        }
    
        throw new \Exception("No valid storage option is enabled.");
    }
    

}
