<?php

namespace App\Infrastructure\Files;

/**
 * Builds relative paths under FTP root — same tree as Delphi Dir_Catalog_*.
 */
class CatalogPath
{
    public function catalog(string $key): string
    {
        $map = config('product.catalogs', []);
        $path = $map[$key] ?? $key;

        return trim(str_replace('\\', '/', $path), '/');
    }

    public function routing(int $objectId): string
    {
        return $this->catalog('routing').'/'.$objectId;
    }

    public function zakaz(int $objectId): string
    {
        return $this->catalog('zakaz').'/'.$objectId;
    }

    public function finance(int $objectId): string
    {
        return $this->catalog('finance').'/'.$objectId;
    }

    public function file(int $objectId): string
    {
        return $this->catalog('file').'/'.$objectId;
    }

    public function img(): string
    {
        return $this->catalog('img');
    }
}
