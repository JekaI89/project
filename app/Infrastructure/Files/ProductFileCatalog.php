<?php

namespace App\Infrastructure\Files;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductFileCatalog
{
    public function __construct(
        private readonly CatalogPath $paths,
    ) {}

    public function disk(): Filesystem
    {
        if (empty(config('filesystems.disks.product_ftp.host'))
            || ! class_exists(\League\Flysystem\Ftp\FtpAdapter::class)) {
            return Storage::disk('local');
        }

        return Storage::disk('product_ftp');
    }

    public function list(string $relativeDir): array
    {
        $disk = $this->disk();
        $dir = trim($relativeDir, '/');

        try {
            if (! $disk->exists($dir) && ! $this->directorySeemsPresent($disk, $dir)) {
                return ['ok' => true, 'path' => $dir, 'files' => [], 'missing' => true];
            }

            $files = collect($disk->files($dir))
                ->map(fn (string $path) => [
                    'path' => $path,
                    'name' => basename($path),
                    'size' => $disk->size($path),
                    'modified' => $disk->lastModified($path),
                ])
                ->values()
                ->all();

            return ['ok' => true, 'path' => $dir, 'files' => $files, 'missing' => false];
        } catch (Throwable $e) {
            return ['ok' => false, 'path' => $dir, 'files' => [], 'error' => $e->getMessage()];
        }
    }

    public function listRouting(int $objectId): array
    {
        return $this->list($this->paths->routing($objectId));
    }

    public function ping(): array
    {
        try {
            $this->disk()->directories('/');

            return ['ok' => true, 'message' => 'ftp/local ok'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function directorySeemsPresent(Filesystem $disk, string $dir): bool
    {
        try {
            $disk->files($dir);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
