<?php

namespace App\Support;

use App\Models\Konfigurasi;
use App\Models\Nasabah;

class KodeJurusan
{
    public const DEFAULT = 'RPL';

    /**
     * Daftar kode jurusan yang sah untuk format rekening.
     * Sumber utama adalah tabel konfigurasi (diubah Admin).
     */
    public static function daftar(): array
    {
        return Konfigurasi::kodeJurusan();
    }

    /**
     * Petakan label kelas ke kode jurusan singkat.
     * Contoh: "XII RPL 1" -> "RPL", "XI DKV 2" -> "DKV", "XII AK 1" -> "AK".
     */
    public static function dariKelas(?string $kelas): string
    {
        $kelas = mb_strtoupper(trim((string) $kelas));

        if ($kelas === '') {
            return self::DEFAULT;
        }

        $pola = self::daftar();

        usort($pola, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($pola as $kode) {
            if (preg_match('/(?:^|\s)'.preg_quote($kode, '/').'(?=\s|$)/', $kelas)) {
                return $kode;
            }
        }

        // Ekstrak kata pertama yang hanya terdiri dari huruf kapital (mis. "XII RPL 1" -> "RPL").
        if (preg_match('/(?<![A-Z])([A-Z]{2,6})(?![A-Z])/', $kelas, $matches)) {
            $kandidat = $matches[1];

            if (! in_array($kandidat, ['X', 'XI', 'XII'], true) && in_array($kandidat, $pola, true)) {
                return $kandidat;
            }
        }

        return self::DEFAULT;
    }

    /**
     * Ambil kode jurusan rekening nasabah, mengutamakan nilai tersimpan.
     */
    public static function untukNasabah(Nasabah $nasabah): string
    {
        return ! empty($nasabah->kode_jurusan) ? $nasabah->kode_jurusan : self::dariKelas($nasabah->kelas);
    }
}
