<?php

namespace App\Support;

use App\Models\Nasabah;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR Code / QRIS nasabah.
 *
 * Sifat yang dijaga:
 * - STABIL: isi QR diturunkan dari nomor rekening, tidak ada nonce, token,
 *   atau timestamp. QR yang sama berlaku selamanya dan tidak berubah
 *   setiap kali halaman dibuka.
 * - BERKALI-KALI: QR tidak punya status "terpakai", sehingga Nasabah yang sama
 *   dapat memindai QR-nya berkali-kali.
 * - TERHUBUNG BENAR: QR memuat nomor rekening nasabah tersebut, sehingga hasil
 *   scan selalu mengarah ke nasabah yang sama.
 *
 * Teller cukup memindai QR; sistem QR tidak perlu diganti karena isi QR
 * sudah berupa nomor rekening yang dicari di tabel `nasabah`.
 */
class QrisNasabah
{
    /**
     * Nilai yang dipayload ke QR. Selalu nomor rekening agar stabil dan
     * bisa dipindai berulang.
     */
    public static function payload(Nasabah $nasabah): string
    {
        return $nasabah->no_rekening;
    }

    /**
     * QR sebagai SVG inline.
     */
    public static function svg(Nasabah $nasabah, int $ukuran = 120, int $margin = 1): string
    {
        return QrCode::size($ukuran)->margin($margin)->generate(self::payload($nasabah));
    }

    /**
     * Cari nasabah dari hasil scan QR / input teller.
     *
     * Hanya nasabah AKTIF (belum diarsipkan) yang dikembalikan agar QRIS
     * nasabah nonaktif tidak dapat dipakai untuk transaksi.
     */
    public static function cari(string $kode): ?Nasabah
    {
        $kode = trim($kode);

        if ($kode === '') {
            return null;
        }

        return Nasabah::with('user')
            ->where('arsip', false)
            ->where(function ($q) use ($kode) {
                $q->where('no_rekening', $kode)
                    ->orWhere('qr_code', $kode)
                    ->orWhere('nis', $kode);
            })
            ->first();
    }
}
