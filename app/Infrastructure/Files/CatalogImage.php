<?php

namespace App\Infrastructure\Files;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Delphi Img_Low / Image_Show paths under Dir_Catalog_Img (files_product).
 */
class CatalogImage
{
    public const KIND_GOODS = 'goods';
    public const KIND_INV = 'inv';
    public const KIND_KIT = 'kit';

    public function __construct(
        private readonly CatalogPath $paths,
    ) {}

    public function resolvePath(int $id, string $kind = self::KIND_GOODS, string $size = 'low'): ?string
    {
        if ($id <= 0) {
            return null;
        }

        $names = $this->candidateNames($id, $kind, $size);
        $roots = $this->localRoots();

        foreach ($roots as $root) {
            foreach ($names as $name) {
                $full = rtrim($root, "\\/").DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $name);
                if (is_file($full) && is_readable($full)) {
                    return $full;
                }
            }
        }

        return null;
    }

    public function resolveStorageRelative(int $id, string $kind = self::KIND_GOODS, string $size = 'low'): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $names = $this->candidateNames($id, $kind, $size);
        $disks = ['local'];
        if (! empty(config('filesystems.disks.product_ftp.host'))) {
            $disks[] = 'product_ftp';
        }

        foreach ($disks as $diskName) {
            try {
                $disk = Storage::disk($diskName);
            } catch (Throwable) {
                continue;
            }
            foreach ($names as $name) {
                try {
                    if ($disk->exists($name)) {
                        return ['disk' => $diskName, 'path' => $name];
                    }
                } catch (Throwable) {
                }
            }
        }

        return null;
    }

    public function url(int $id, string $kind = self::KIND_GOODS, string $size = 'low'): string
    {
        return url(sprintf('/catalog/img/%s/%d', $kind, $id).($size === 'full' ? '?size=full' : ''));
    }

    public function candidateNames(int $id, string $kind, string $size): array
    {
        $pref = match ($kind) {
            self::KIND_INV => 'inv_',
            self::KIND_KIT => 'kit_',
            default => '',
        };
        $suffix = $size === 'full' ? '.jpg' : '_low.jpg';
        $base = $pref.$id.$suffix;
        $imgDir = $this->paths->img();

        $list = [
            $base,
            $imgDir.'/'.$base,
        ];

        if ($size === 'low') {
            $full = $pref.$id.'.jpg';
            $list[] = $full;
            $list[] = $imgDir.'/'.$full;
        }

        return $list;
    }

    private function localRoots(): array
    {
        $roots = [];
        $configured = (string) config('product.img_path', env('PRODUCT_IMG_PATH', ''));
        if ($configured !== '' && is_dir($configured)) {
            $roots[] = $configured;
        }

        foreach (['E:\\files_product', 'E:/files_product', '/mnt/files_product', '/var/files_product'] as $p) {
            if (is_dir($p)) {
                $roots[] = $p;
            }
        }

        $private = storage_path('app/private');
        if (is_dir($private)) {
            $roots[] = $private;
        }

        return array_values(array_unique($roots));
    }
}
