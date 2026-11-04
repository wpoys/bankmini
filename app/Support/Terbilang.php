<?php

namespace App\Support;

/**
 * Konversi angka ke huruf (terbilang) untuk form & struk transaksi.
 * Murni PHP — tanpa dependensi eksternal.
 */
class Terbilang
{
    private const SATUAN = [
        '', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas',
    ];

    /**
     * Konversi bilangan bulat tak-negatif ke huruf.
     */
    public static function angka(int $n): string
    {
        if ($n < 0) {
            return 'minus '.self::angka(-$n);
        }

        if ($n < 12) {
            return self::SATUAN[$n];
        }

        if ($n < 20) {
            return self::angka($n - 10).' belas';
        }

        if ($n < 100) {
            $depan = self::angka(intdiv($n, 10));
            $belakang = self::angka($n % 10);

            return trim($depan.' puluh '.$belakang);
        }

        if ($n < 200) {
            return 'seratus '.self::angka($n - 100);
        }

        if ($n < 1000) {
            return trim(self::angka(intdiv($n, 100)).' ratus '.self::angka($n % 100));
        }

        if ($n < 2000) {
            return 'seribu '.self::angka($n - 1000);
        }

        if ($n < 1000000) {
            return trim(self::angka(intdiv($n, 1000)).' ribu '.self::angka($n % 1000));
        }

        if ($n < 1000000000) {
            return trim(self::angka(intdiv($n, 1000000)).' juta '.self::angka($n % 1000000));
        }

        return trim(self::angka(intdiv($n, 1000000000)).' miliar '.self::angka($n % 1000000000));
    }

    /**
     * Terbilang lengkap dalam Rupiah, mis. "seratus ribu rupiah".
     */
    public static function rupiah(?int $n): string
    {
        $n = (int) $n;

        if ($n === 0) {
            return 'nol rupiah';
        }

        return trim(preg_replace('/\s+/', ' ', self::angka($n))).' rupiah';
    }
}
