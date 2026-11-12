<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * Determines the active upload disk: 's3' if valid credentials and bucket are present, otherwise 'public'.
     */
    public static function defaultUploadDisk(): string
    {
        if (self::hasS3Credentials()) {
            return 's3';
        }

        return 'public';
    }

    /**
     * Checks whether AWS S3 configuration parameters are configured in the environment.
     */
    public static function hasS3Credentials(): bool
    {
        $key = Config::get('filesystems.disks.s3.key');
        $secret = Config::get('filesystems.disks.s3.secret');
        $bucket = Config::get('filesystems.disks.s3.bucket');
        $region = Config::get('filesystems.disks.s3.region');

        return !empty($key) && !empty($secret) && !empty($bucket) && !empty($region);
    }

    /**
     * Stores an uploaded file on S3 if credentials are available; falls back seamlessly to the local 'public' disk on any S3 exception or if credentials are missing.
     *
     * @return array{path: string, disk: string}
     */
    public static function storeUploadedFile(UploadedFile $file, string $directory): array
    {
        if (self::hasS3Credentials()) {
            try {
                $path = $file->store($directory, 's3');
                if ($path) {
                    return [
                        'path' => $path,
                        'disk' => 's3',
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("AWS S3 upload failed for {$file->getClientOriginalName()}, falling back to public disk: " . $e->getMessage());
            }
        }

        // Default local public storage fallback
        $path = $file->store($directory, 'public');

        return [
            'path' => $path,
            'disk' => 'public',
        ];
    }

    /**
     * Resolves a public URL or download URL for a stored file across S3 or local public storage.
     */
    public static function url(string $path, ?string $disk = null): string
    {
        $disk = $disk ?: (self::hasS3Credentials() ? 's3' : 'public');

        if ($disk === 's3') {
            try {
                return Storage::disk('s3')->url($path);
            } catch (\Throwable $e) {
                // If url resolution fails, fallback to local url or direct storage
                return asset('storage/' . $path);
            }
        }

        return asset('storage/' . $path);
    }
}

