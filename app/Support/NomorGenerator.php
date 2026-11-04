<?php

namespace App\Support;

use App\Models\Nasabah;
use App\Models\NomorSequence;
use Illuminate\Support\Facades\DB;

class NomorGenerator
{
    /**
     * Tahun sentinel untuk sequence yang tidak boleh reset per tahun
     * (mis. nomor rekening yang harus kontinu agar tidak kembali ke RPL-001).
     */
    public const TAHUN_KONTINU = 1;

    protected static function ambilUrutan(string $nama, int $tahun): int
    {
        return DB::transaction(function () use ($nama, $tahun) {
            $sequence = NomorSequence::query()
                ->lockForUpdate()
                ->where('nama', $nama)
                ->where('tahun', $tahun)
                ->first();

            if (! $sequence) {
                $sequence = NomorSequence::firstOrCreate([
                    'nama' => $nama,
                    'tahun' => $tahun,
                ]);
            }

            $sequence->increment('nilai');

            return (int) $sequence->fresh()->nilai;
        });
    }

    /**
     * Nomor struk berurutan per tahun, contoh: TRX-2026-000001.
     */
    public static function struk(): string
    {
        $tahun = now()->year;
        $urutan = static::ambilUrutan('struk', $tahun);

        return sprintf('TRX-%d-%06d', $tahun, $urutan);
    }

    /**
     * Nomor rekening berurutan per kode jurusan, contoh: RPL-001, DKV-003.
     *
     * - Format tetap KodeJurusan + strip + nomor urut 3 digit (zero-padded).
     * - Sequence TIDAK reset per tahun (kontinu) agar tidak kembali ke
     *   RPL-001 pada tahun berikutnya selama rekening lama masih ada.
     * - Aman terhadap race condition: baris sequence dikunci (lockForUpdate)
     *   di dalam satu transaksi DB.
     * - Melompati nomor yang sudah terpakai oleh data lama bila terjadi
     *   ketidakcocokan sequence.
     *
     * @throws \InvalidArgumentException bila kode jurusan tidak terdaftar
     *                                   pada tabel konfigurasi.
     */
    public static function rekening(string $kodeJurusan = 'RPL'): string
    {
        $kodeJurusan = strtoupper(trim($kodeJurusan)) ?: 'RPL';

        if (! KodeJurusan::daftar() || ! in_array($kodeJurusan, KodeJurusan::daftar(), true)) {
            throw new \InvalidArgumentException(
                "Kode jurusan '{$kodeJurusan}' tidak terdaftar pada konfigurasi. Hubungi Administrator untuk menambahkan kode jurusan."
            );
        }

        $nama = 'rekening_'.$kodeJurusan;

        for ($percobaan = 0; $percobaan < 100; $percobaan++) {
            $urutan = static::ambilUrutan($nama, self::TAHUN_KONTINU);
            $nomor = sprintf('%s-%03d', $kodeJurusan, $urutan);

            // Lewati nomor lama format tanpa strip (mis. RPL001) agar tidak
            // terjadi duplikasi logis dengan data sebelum migrasi format.
            $kemungkinanLama = sprintf('%s%03d', $kodeJurusan, $urutan);

            if (! Nasabah::whereIn('no_rekening', [$nomor, $kemungkinanLama])->exists()) {
                return $nomor;
            }
        }

        throw new \RuntimeException('Gagal menghasilkan nomor rekening unik. Silakan coba lagi.');
    }
}
