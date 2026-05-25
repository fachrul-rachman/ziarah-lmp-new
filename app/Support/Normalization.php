<?php

namespace App\Support;

use Illuminate\Support\Str;

class Normalization
{
    public static function normalizeText(string $value): string
    {
        return Str::of($value)
            ->trim()
            ->replaceMatches('/\s+/', ' ')
            ->toString();
    }

    public static function normalizeLotNumber(string $value): string
    {
        return Str::of(self::normalizeText($value))
            ->lower()
            ->toString();
    }

    public static function normalizeSizeKey(string $value): string
    {
        return Str::of(self::normalizeText($value))
            ->lower()
            ->toString();
    }

    public static function normalizeSizeDisplay(string $value): string
    {
        // Title Case display, based on normalized spacing.
        return Str::of(self::normalizeText($value))
            ->lower()
            ->title()
            ->toString();
    }

    /**
     * Normalize Indonesian phone number to digits starting with "62".
     * Accepts inputs like: 08xxxxxxxxxx, 62xxxxxxxxxx, +62xxxxxxxxxx.
     *
     * @throws \RuntimeException when invalid.
     */
    public static function normalizePhoneId(string $value, int $minDigits = 10, int $maxDigits = 15): string
    {
        $raw = self::normalizeText($value);
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            throw new \RuntimeException('Nomor telepon wajib diisi.');
        }

        // Convert leading 0 -> 62
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        // Only allow numbers starting with 62
        if (! str_starts_with($digits, '62')) {
            throw new \RuntimeException('Nomor telepon harus diawali 08 atau 62.');
        }

        $len = strlen($digits);
        if ($len < $minDigits) {
            throw new \RuntimeException("Nomor telepon terlalu pendek (minimal {$minDigits} digit).");
        }
        if ($len > $maxDigits) {
            throw new \RuntimeException("Nomor telepon terlalu panjang (maksimal {$maxDigits} digit).");
        }

        return $digits;
    }
}
