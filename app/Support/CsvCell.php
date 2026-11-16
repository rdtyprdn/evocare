<?php

namespace App\Support;

/**
 * Amankan nilai sel CSV dari formula injection (CSV injection).
 *
 * Nilai teks yang diawali =, +, -, @, tab, atau carriage return (termasuk setelah spasi di depan)
 * diberi awalan tanda kutip tunggal agar Excel / Spreadsheet membacanya sebagai teks biasa.
 * Angka murni seperti "-5" atau "65" dan tanda "-" tunggal dibiarkan apa adanya.
 */
class CsvCell
{
    public static function escape(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }
        // Satu karakter saja (misalnya "-" sebagai pengganti data kosong) atau angka murni tidak berbahaya.
        if (mb_strlen($value) === 1 || preg_match('/^[+-]?\d+([.,]\d+)?$/', $value)) {
            return $value;
        }
        if (preg_match('/^[\t\r]/', $value) || preg_match('/^\s*[=+\-@]/u', $value)) {
            return "'".$value;
        }

        return $value;
    }

    /** Amankan seluruh sel dalam satu baris CSV. */
    public static function row(array $cells): array
    {
        return array_map([self::class, 'escape'], $cells);
    }
}
