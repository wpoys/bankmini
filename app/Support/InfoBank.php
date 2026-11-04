<?php

namespace App\Support;

/**
 * Identitas Bank Mini Sekolah yang dipakai pada struk transaksi
 * (layar, cetak, dan PDF) agar tampilan formal dan konsisten.
 */
class InfoBank
{
    public const NAMA = 'BANK MINI SEKOLAH';

    public const JENIS = 'Bank Mini Sekolah';

    public const TAGLINE = 'Mudah, Aman, Terpercaya';

    public const ALAMAT = 'SMKN 1 Bantul, Jl. Pendidikan No. 1, Kabupaten Bantul';

    public const LEMBAGA = 'Sistem Informasi E-Teller';

    public const KETERANGAN = 'Bukti transaksi resmi Bank Mini Sekolah, sah dan sahaja sebagai tanda pembayaran.';

    /**
     * Path logo relatif folder public (opsional).
     */
    public static function logoPath(): ?string
    {
        $candidates = [
            'images/logo smkn 1 bantul.png',
            'images/logo.png',
        ];

        foreach ($candidates as $file) {
            if (is_file(public_path($file))) {
                return $file;
            }
        }

        return null;
    }

    /**
     * URL logo untuk ditampilkan di browser (blade layar & cetak).
     */
    public static function logoUrl(): ?string
    {
        return static::logoPath() ? asset(static::logoPath()) : null;
    }

    /**
     * Logo sebagai data URI (dipakai PDF agar tidak perlu URL eksternal).
     */
    public static function logoDataUri(): ?string
    {
        $path = static::logoPath();

        if (! $path) {
            return null;
        }

        $content = @file_get_contents(public_path($path));

        if ($content === false) {
            return null;
        }

        $mime = @mime_content_type(public_path($path)) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }
}
