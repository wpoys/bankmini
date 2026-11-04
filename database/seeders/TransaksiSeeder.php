<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\DetailLaporan;
use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $tellers = User::where('role', 'teller')->orderBy('id')->get();
        $supervisor = User::where('role', 'supervisor')->first();
        $nasabah = Nasabah::orderBy('id')->get();

        $baseSaldo = [500000, 350000, 250000, 800000, 450000, 300000, 600000, 200000, 750000, 400000, 150000, 250000];
        $runningSaldo = [];

        foreach ($nasabah as $i => $n) {
            $runningSaldo[$n->id] = $baseSaldo[$i] ?? 250000;
        }

        $nominalChoices = [10000, 15000, 20000, 25000, 30000, 50000, 75000, 100000, 150000, 200000, 250000, 300000];
        $kasSaldo = [];
        $noStrukSeq = 1;
        $laporanList = [];
        $activityLogs = [];

        foreach ($tellers as $teller) {
            $kasSaldo[$teller->id] = 10000000;
        }

        mt_srand(2026);

        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $date = today()->subDays($daysAgo);

            foreach ($tellers as $teller) {
                $totalSetoran = 0;
                $totalPenarikan = 0;
                $dayTransactions = [];

                $jumlahTransaksi = mt_rand(2, 4);

                for ($n = 0; $n < $jumlahTransaksi; $n++) {
                    $nasabahItem = $nasabah->random();
                    $jenis = mt_rand(1, 10) <= 6 ? 'setoran' : 'penarikan';
                    $nominal = $nominalChoices[array_rand($nominalChoices)];

                    if ($jenis === 'penarikan' && $runningSaldo[$nasabahItem->id] < $nominal) {
                        $jenis = 'setoran';
                    }

                    if ($jenis === 'setoran') {
                        $runningSaldo[$nasabahItem->id] += $nominal;
                        $totalSetoran += $nominal;
                    } else {
                        $runningSaldo[$nasabahItem->id] -= $nominal;
                        $totalPenarikan += $nominal;
                    }

                    $noStruk = 'TRX-'.$date->format('Ymd').'-'.str_pad($noStrukSeq++, 6, '0', STR_PAD_LEFT);

                    $transaksi = Transaksi::create([
                        'id_nasabah' => $nasabahItem->id,
                        'id_user' => $teller->id,
                        'tanggal' => $date,
                        'jenis_trans' => $jenis,
                        'nominal' => $nominal,
                        'no_struk' => $noStruk,
                        'status' => 'approved',
                        'created_at' => $date->copy()->setTime(mt_rand(8, 14), mt_rand(0, 59)),
                        'updated_at' => $date->copy()->setTime(14, 0),
                    ]);

                    $this->catatJurnal($transaksi->id, $nominal, $jenis);

                    $dayTransactions[] = $transaksi->id;

                    $activityLogs[] = [
                        'id_user' => $teller->id,
                        'aktivitas' => $jenis,
                        'deskripsi' => ucfirst($jenis).' Rp '.number_format($nominal, 0, ',', '.').
                            " atas nama {$nasabahItem->nama_siswa} (no struk {$noStruk}).",
                        'ip_address' => '127.0.0.1',
                        'created_at' => $transaksi->created_at,
                        'updated_at' => $transaksi->created_at,
                    ];
                }

                $saldoAwalKas = $kasSaldo[$teller->id];
                $saldoAkhirSistem = $saldoAwalKas + $totalSetoran - $totalPenarikan;
                $kasSaldo[$teller->id] = $saldoAkhirSistem;

                $status = $this->statusUntuk($daysAgo, $teller->id === $tellers->first()->id);

                $dataLaporan = [
                    'teller_id' => $teller->id,
                    'tanggal' => $date,
                    'saldo_awal_kas' => $saldoAwalKas,
                    'total_setoran' => $totalSetoran,
                    'total_penarikan' => $totalPenarikan,
                    'saldo_akhir_sistem' => $saldoAkhirSistem,
                    'status' => $status,
                    'created_at' => $date->copy()->setTime(15, 0),
                    'updated_at' => $date->copy()->setTime(15, 0),
                ];

                if ($status === 'disetujui') {
                    $dataLaporan['saldo_akhir_fisik'] = $saldoAkhirSistem;
                    $dataLaporan['selisih'] = 0;
                    $dataLaporan['approved_by'] = $supervisor->id;
                    $dataLaporan['approved_at'] = $date->copy()->setTime(16, 30);
                    $dataLaporan['submitted_at'] = $date->copy()->setTime(15, 10);
                    $dataLaporan['catatan_supervisor'] = 'Laporan sesuai dengan mutasi transaksi.';
                } elseif ($status === 'ditolak') {
                    $dataLaporan['saldo_akhir_fisik'] = $saldoAkhirSistem - 100000;
                    $dataLaporan['selisih'] = -100000;
                    $dataLaporan['approved_by'] = $supervisor->id;
                    $dataLaporan['approved_at'] = $date->copy()->setTime(16, 45);
                    $dataLaporan['submitted_at'] = $date->copy()->setTime(15, 10);
                    $dataLaporan['catatan_supervisor'] = 'Selisih kas fisik cukup besar, mohon perbaiki kembali.';
                } elseif ($status === 'menunggu_approval') {
                    $dataLaporan['saldo_akhir_fisik'] = $saldoAkhirSistem;
                    $dataLaporan['selisih'] = 0;
                    $dataLaporan['submitted_at'] = $date->copy()->setTime(15, 10);
                } else {
                    $dataLaporan['saldo_akhir_fisik'] = $saldoAkhirSistem;
                    $dataLaporan['selisih'] = 0;
                }

                $laporan = LaporanHarian::create($dataLaporan);

                foreach ($dayTransactions as $idTransaksi) {
                    DetailLaporan::firstOrCreate([
                        'laporan_id' => $laporan->id,
                        'transaksi_id' => $idTransaksi,
                    ]);
                }

                $laporanList[] = $laporan;
            }
        }

        foreach ($nasabah as $n) {
            $n->update(['saldo' => $runningSaldo[$n->id]]);
        }

        $this->catatActivity($activityLogs);

        $this->catatAktivitasOperasional($supervisor);

        $this->catatKoreksiMenunggu($supervisor);
    }

    protected function statusUntuk(int $daysAgo, bool $tellerPertama): string
    {
        if ($daysAgo >= 4) {
            return 'disetujui';
        }

        return match ($daysAgo) {
            3 => $tellerPertama ? 'disetujui' : 'ditolak',
            2 => $tellerPertama ? 'menunggu_approval' : 'disetujui',
            1 => 'menunggu_approval',
            0 => 'draft',
            default => 'disetujui',
        };
    }

    /**
     * Buat contoh koreksi setoran/penarikan berstatus pending agar
     * modul Verifikasi Koreksi milik Accounting langsung terisi.
     */
    protected function catatKoreksiMenunggu(User $supervisor): void
    {
        $teller = User::where('role', 'teller')->orderBy('id')->first();
        $nasabah = Nasabah::orderBy('id')->get();

        if (! $teller || $nasabah->count() < 3) {
            return;
        }

        $satu = $nasabah->get(2);
        $dua = $nasabah->get(3);

        Transaksi::create([
            'id_nasabah' => $satu->id,
            'id_user' => $teller->id,
            'tanggal' => today(),
            'jenis_trans' => 'koreksi_setoran',
            'nominal' => 50000,
            'status' => 'pending',
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        if ((int) $dua->saldo >= 30000) {
            Transaksi::create([
                'id_nasabah' => $dua->id,
                'id_user' => $teller->id,
                'tanggal' => today(),
                'jenis_trans' => 'koreksi_penarikan',
                'nominal' => 30000,
                'status' => 'pending',
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ]);
        }
    }

    protected function catatJurnal(int $idTransaksi, int $nominal, string $jenis): void
    {
        $entries = [];

        if ($jenis === 'setoran') {
            $entries = [
                ['kode_akun' => 'Kas_101', 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => 'Tabungan_201', 'posisi' => 'kredit', 'jumlah' => $nominal],
            ];
        } else {
            $entries = [
                ['kode_akun' => 'Tabungan_201', 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => 'Kas_101', 'posisi' => 'kredit', 'jumlah' => $nominal],
            ];
        }

        foreach ($entries as $entry) {
            DB::table('jurnal_akuntansi')->insert([
                'id_transaksi' => $idTransaksi,
                'kode_akun' => $entry['kode_akun'],
                'posisi' => $entry['posisi'],
                'jumlah' => $entry['jumlah'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function catatActivity(array $logs): void
    {
        $chunks = array_chunk($logs, 500);

        foreach ($chunks as $chunk) {
            DB::table('activity_logs')->insert($chunk);
        }
    }

    protected function catatAktivitasOperasional(User $supervisor): void
    {
        $admin = User::where('role', 'admin')->first();
        $tellers = User::where('role', 'teller')->orderBy('id')->get();

        if (! $admin || ! $supervisor || $tellers->isEmpty()) {
            return;
        }

        $teller1 = $tellers[0];
        $teller2 = $tellers[1] ?? $tellers[0];

        $logs = [
            ['id_user' => $admin->id, 'aktivitas' => 'login', 'deskripsi' => 'Administrator login ke sistem.', 'created_at' => now()->subDays(13)->setTime(7, 50)],
            ['id_user' => $supervisor->id, 'aktivitas' => 'login', 'deskripsi' => 'Supervisor login ke sistem.', 'created_at' => now()->subDays(13)->setTime(8, 0)],
            ['id_user' => $teller1->id, 'aktivitas' => 'login', 'deskripsi' => 'Teller login ke sistem.', 'created_at' => now()->subDays(13)->setTime(7, 55)],
            ['id_user' => $teller2->id, 'aktivitas' => 'login', 'deskripsi' => 'Teller login ke sistem.', 'created_at' => now()->subDays(13)->setTime(7, 57)],
            ['id_user' => $supervisor->id, 'aktivitas' => 'approve_laporan', 'deskripsi' => 'Laporan harian tanggal '.now()->subDays(12)->format('Y-m-d').' disetujui.', 'created_at' => now()->subDays(12)->setTime(16, 30)],
            ['id_user' => $supervisor->id, 'aktivitas' => 'tolak_laporan', 'deskripsi' => 'Laporan harian tanggal '.now()->subDays(3)->format('Y-m-d').' ditolak karena selisih kas.', 'created_at' => now()->subDays(3)->setTime(16, 45)],
            ['id_user' => $teller1->id, 'aktivitas' => 'buat_laporan', 'deskripsi' => 'Laporan harian dibuat untuk tanggal '.now()->format('Y-m-d').'.', 'created_at' => now()->setTime(15, 0)],
            ['id_user' => $teller2->id, 'aktivitas' => 'buat_laporan', 'deskripsi' => 'Laporan harian dibuat untuk tanggal '.now()->format('Y-m-d').'.', 'created_at' => now()->setTime(15, 2)],
        ];

        foreach ($logs as $log) {
            $log['ip_address'] = '127.0.0.1';
            $log['updated_at'] = $log['created_at'];
            ActivityLog::create($log);
        }
    }
}
