<?php

namespace App\Support;

/** Validasi tujuan redirect agar hanya path internal (mencegah open redirect). */
class SafeRedirect
{
    public static function path(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 500) {
            return null;
        }
        if ($value[0] !== '/' || str_starts_with($value, '//') || str_contains($value, '\\') || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return null;
        }
        $parts = parse_url($value);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        return $value;
    }

    /** Simpan sebagai intended URL bila valid. */
    public static function remember(mixed $value): void
    {
        if ($path = self::path($value)) {
            session(['url.intended' => url($path)]);
        }
    }
}
