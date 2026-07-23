<?php

namespace App\Domain\Support;

/**
 * Safe access to stdClass / array rows returned from product MySQL.
 */
final class RowMapper
{
    public static function get(object|array $row, string $key, mixed $default = null): mixed
    {
        if (is_array($row)) {
            return array_key_exists($key, $row) ? $row[$key] : $default;
        }

        return property_exists($row, $key) ? $row->{$key} : $default;
    }

    public static function float(object|array $row, string $key, int $decimals = -6): float
    {
        return DelphiMath::rnd(self::get($row, $key, 0), $decimals);
    }

    public static function int(object|array $row, string $key, int $default = 0): int
    {
        return (int) self::get($row, $key, $default);
    }

    public static function str(object|array $row, string $key, string $default = ''): string
    {
        $v = self::get($row, $key, $default);

        return $v === null ? $default : (string) $v;
    }

    public static function bool(object|array $row, string $key): bool
    {
        return DelphiMath::intBool(self::get($row, $key, 0));
    }

    /** Convert arbitrary DB row to associative array (for Inertia). */
    public static function toArray(object|array $row): array
    {
        return is_array($row) ? $row : (array) $row;
    }
}
