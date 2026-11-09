<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Brankas;
use App\Models\MutasiBrankas;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BrankasService
{
    public static function saldo(): int
    {
        return Brankas::saldo();
    }

    public static function aturSaldoAwal(int $jumlah, int $aktorId): void
    {
        DB::transaction(function () use ($jumlah, $aktorId) {
            $brankas = Brankas::query()->lockForUpdate()->findOrFail(1);
            $perubahan = $jumlah - (int) $brankas->saldo;
            $brankas->update(['saldo' => $jumlah]);

            if ($perubahan !== 0) {
                MutasiBrankas::create([
                    'brankas_id' => $brankas->id,
                    'user_id' => $aktorId,
                    'jenis' => 'koreksi_admin',
                    'nominal' => $perubahan,
                    'keterangan' => 'Penyesuaian saldo brankas oleh admin',
                ]);
            }
        });

        ActivityLog::catat($aktorId, 'ubah_saldo_brankas', 'Saldo brankas disesuaikan menjadi Rp '.number_format($jumlah, 0, ',', '.').'.');
    }

    public static function ambilKas(int $jumlah, int $tellerId): void
    {
        DB::transaction(function () use ($jumlah, $tellerId) {
            $brankas = Brankas::query()->lockForUpdate()->findOrFail(1);

            if ($jumlah > (int) $brankas->saldo) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Saldo brankas tidak mencukupi.',
                ]);
            }

            $brankas->decrement('saldo', $jumlah);
            MutasiBrankas::create([
                'brankas_id' => $brankas->id,
                'user_id' => $tellerId,
                'jenis' => 'kas_awal_teller',
                'nominal' => -$jumlah,
                'keterangan' => 'Kas awal diambil teller',
            ]);
        });

        ActivityLog::catat($tellerId, 'ambil_kas_brankas', 'Teller mengambil kas awal Rp '.number_format($jumlah, 0, ',', '.').' dari brankas.');
    }

    public static function setorDariLaporan(int $jumlah, int $tellerId, int $laporanId): void
    {
        if ($jumlah <= 0 || MutasiBrankas::where('laporan_id', $laporanId)->where('jenis', 'setoran_laporan')->exists()) {
            return;
        }

        DB::transaction(function () use ($jumlah, $tellerId, $laporanId) {
            $brankas = Brankas::query()->lockForUpdate()->findOrFail(1);

            if (MutasiBrankas::where('laporan_id', $laporanId)->where('jenis', 'setoran_laporan')->exists()) {
                return;
            }

            $brankas->increment('saldo', $jumlah);
            MutasiBrankas::create([
                'brankas_id' => $brankas->id,
                'user_id' => $tellerId,
                'laporan_id' => $laporanId,
                'jenis' => 'setoran_laporan',
                'nominal' => $jumlah,
                'keterangan' => 'Kas dikembalikan dari laporan teller yang disetujui',
            ]);
        });
    }
}
