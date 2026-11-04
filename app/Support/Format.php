<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Format tampilan portal nasabah (Bahasa Indonesia).
 * Semua nominal memakai pemisah ribuan titik: Rp 1.250.000.
 */
class Format
{
    public static function rupiah(int|float|string|null $nilai): string
    {
        $angka = (int) round((float) ($nilai ?? 0));

        return 'Rp '.number_format($angka, 0, ',', '.');
    }

    public static function angka(int|float|string|null $nilai): string
    {
        return number_format((int) round((float) ($nilai ?? 0)), 0, ',', '.');
    }

    public static function tanggal(CarbonInterface|string|null $tanggal, string $pola = 'd M Y'): string
    {
        if ($tanggal === null || $tanggal === '') {
            return '-';
        }

        $carbon = $tanggal instanceof CarbonInterface
            ? $tanggal
            : Carbon::parse($tanggal);

        return $carbon->locale('id')->translatedFormat($pola);
    }

    public static function tanggalJam(CarbonInterface|string|null $tanggal): string
    {
        return self::tanggal($tanggal, 'd M Y • H:i');
    }

    public static function jam(CarbonInterface|string|null $tanggal): string
    {
        return self::tanggal($tanggal, 'H:i');
    }

    /**
     * Masking email: budi.santoso@sekolah.id -> bu***@sekolah.id
     */
    public static function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '-';
        }

        [$nama, $domain] = explode('@', $email, 2);
        $awal = mb_substr($nama, 0, 2);

        return $awal.str_repeat('*', max(2, mb_strlen($nama) - 2)).'@'.$domain;
    }

    /**
     * Masking nomor HP: 081234567890 -> 0812****890
     */
    public static function maskTelepon(?string $telepon): string
    {
        $telepon = preg_replace('/\D+/', '', (string) $telepon);

        if ($telepon === '') {
            return '-';
        }

        if (strlen($telepon) <= 7) {
            return substr($telepon, 0, 2).str_repeat('*', max(1, strlen($telepon) - 4)).substr($telepon, -2);
        }

        return substr($telepon, 0, 4).str_repeat('*', strlen($telepon) - 7).substr($telepon, -3);
    }

    public static function salam(?CarbonInterface $waktu = null): string
    {
        $jam = (int) ($waktu ?? now('Asia/Jakarta'))->format('G');

        return match (true) {
            $jam < 11 => 'Pagi',
            $jam < 15 => 'Siang',
            $jam < 19 => 'Sore',
            default => 'Malam',
        };
    }
}
