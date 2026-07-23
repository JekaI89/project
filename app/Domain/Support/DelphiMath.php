<?php

namespace App\Domain\Support;

/**
 * Mirrors Delphi helpers used when mapping SP rows (Store.pas / Store2.pas).
 */
final class DelphiMath
{
    /**
     * Round like Delphi rnd(value, -decimals) used in Product.
     * Negative second arg means decimal places (e.g. -6 -> 6 decimals, -2 -> 2).
     */
    public static function rnd(mixed $value, int $decimals = -6): float
    {
        $places = abs($decimals);
        $n = is_numeric($value) ? (float) $value : 0.0;

        return round($n, $places);
    }

    public static function boolInt(mixed $v): int
    {
        if (is_bool($v)) {
            return $v ? 1 : 0;
        }

        return ((int) $v) !== 0 ? 1 : 0;
    }

    public static function intBool(mixed $v): bool
    {
        return ((int) $v) === 1;
    }

    /** Delphi ReplaceChar: comma -> dot for float strings */
    public static function replaceChar(string $s): string
    {
        return str_replace(',', '.', $s);
    }
}
