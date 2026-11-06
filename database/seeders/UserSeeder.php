<?php

namespace Database\Seeders;

use App\Models\Nasabah;
use App\Models\NomorSequence;
use App\Models\PermintaanResetPin;
use App\Models\User;
use App\Support\KodeJurusan;
use App\Support\NomorGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(KonfigurasiSeeder::class);
        $this->buatPetugas();

        $nasabah = [
            ['nis' => '20241001', 'nama' => 'Budi Santoso', 'kelas' => 'XI RPL 1', 'pin' => '123456'],
            ['nis' => '20241002', 'nama' => 'Ani Wijaya', 'kelas' => 'XII RPL 2', 'pin' => '654321'],
            ['nis' => '20241003', 'nama' => 'Citra Dewi', 'kelas' => 'XII RPL 1', 'pin' => '789012'],
            ['nis' => '20241004', 'nama' => 'Dewi Lestari', 'kelas' => 'XII RPL 2', 'pin' => '112233'],
            ['nis' => '20241005', 'nama' => 'Eko Prasetyo', 'kelas' => 'XII TKJ 1', 'pin' => '445566'],
            ['nis' => '20241006', 'nama' => 'Fitri Handayani', 'kelas' => 'XII TKJ 2', 'pin' => '778899'],
            ['nis' => '20241007', 'nama' => 'Galih Ramadhan', 'kelas' => 'XI RPL 1', 'pin' => '101010'],
            ['nis' => '20241008', 'nama' => 'Hana Safitri', 'kelas' => 'XI RPL 2', 'pin' => '202020'],
            ['nis' => '20241009', 'nama' => 'Indra Maulana', 'kelas' => 'XI TKJ 1', 'pin' => '303030'],
            ['nis' => '20241010', 'nama' => 'Joko Susilo', 'kelas' => 'XI TKJ 2', 'pin' => '404040'],
            ['nis' => '20241011', 'nama' => 'Kartika Sari', 'kelas' => 'X RPL 1', 'pin' => '505050'],
            ['nis' => '20241012', 'nama' => 'Lukman Hakim', 'kelas' => 'X RPL 2', 'pin' => '606060'],
            ['nis' => '20241013', 'nama' => 'Bambang Sutrisno', 'kelas' => 'XI AK 1', 'pin' => '707070', 'produk' => 'guru'],
        ];

        foreach ($nasabah as $i => $data) {
            $kodeJurusan = KodeJurusan::dariKelas($data['kelas']);

            $this->createNasabah([
                'username' => $data['nis'],
                'email' => "{$data['nis']}@student.bankmini.test",
                'password' => 'Nasabah@2026',
                'nama_petugas' => $data['nama'],
                'no_rekening' => sprintf('%s-%03d', $kodeJurusan, $i + 1),
                'kode_jurusan' => $kodeJurusan,
                'produk' => $data['produk'] ?? 'siswa',
                'nis' => $data['nis'],
                'nama_siswa' => $data['nama'],
                'kelas' => $data['kelas'],
                'pin_keamanan' => $data['pin'],
                'saldo' => 0,
            ]);
        }

        $this->createNasabah([
            'username' => 'wildan',
            'email' => 'wildan@gmail.com',
            'password' => 'Wildan@2026',
            'nama_petugas' => 'Wildan Aji Saputra',
            'no_rekening' => 'DKV-001',
            'kode_jurusan' => 'DKV',
            'produk' => 'siswa',
            'nis' => '20261001',
            'nama_siswa' => 'Wildan Aji Saputra',
            'kelas' => 'XII DKV 1',
            'pin_keamanan' => '135790',
            'saldo' => 0,
        ]);

        $this->sinkronkanNomorRekening();

        $this->sinkronkanPermintaanResetPin();
    }

    /**
     * Buat contoh permintaan reset PIN agar menu CS langsung terlihat isinya.
     */
    protected function sinkronkanPermintaanResetPin(): void
    {
        $admin = User::where('role', 'admin')->first();
        $supervisor = User::where('role', 'supervisor')->first();
        $teller = User::where('role', 'teller')->orderBy('id')->first();
        $nasabah = Nasabah::orderBy('id')->get();

        if (! $admin || $nasabah->count() < 2) {
            return;
        }

        $diproses = $nasabah->first();
        $selesai = now()->subDay();

        PermintaanResetPin::create([
            'id_nasabah' => $diproses->id,
            'keterangan' => 'Permintaan dari loket.',
            'status' => PermintaanResetPin::STATUS_DIPROSES,
            'diajukan_oleh_role' => 'nasabah',
            'diproses_oleh' => $admin->id,
            'teller_id' => $teller?->id,
            'supervisor_id' => $supervisor?->id,
            'nomor_tiket' => 'REQ-PIN-'.$selesai->format('Ymd').'-01',
            'diajukan_pada' => $selesai->copy()->subMinutes(30),
            'catatan' => 'PIN baru telah diverifikasi dan diaktifkan petugas loket.',
            'diproses_at' => $selesai,
            'disetujui_at' => $selesai,
            'created_at' => $selesai->copy()->subMinutes(30),
            'updated_at' => $selesai,
        ]);
    }

    /**
     * Sinkronkan urutan nomor rekening agar penerbitan rekening baru
     * melanjutkan dari nomor urut tertinggi yang sudah dipakai seeder.
     *
     * Sequence nomor rekening sengaja kontinu (tidak reset per tahun)
     * supaya tahun berikutnya tidak kembali menjadi RPL-001.
     */
    protected function sinkronkanNomorRekening(): void
    {
        $tahun = now()->year;
        $tahunKontinu = NomorGenerator::TAHUN_KONTINU;

        Nasabah::query()
            ->whereNotNull('kode_jurusan')
            ->get(['kode_jurusan', 'no_rekening'])
            ->groupBy('kode_jurusan')
            ->each(function ($rows, $jurusan) use ($tahun, $tahunKontinu) {
                $tertinggi = $rows->map(fn ($n) => (int) preg_replace('/\D+/', '', (string) substr((string) $n->no_rekening, strlen((string) $jurusan))) ?: 0)
                    ->max() ?? 0;

                NomorSequence::updateOrCreate(
                    ['nama' => 'rekening_'.$jurusan, 'tahun' => $tahun],
                    ['nilai' => $tertinggi]
                );

                NomorSequence::updateOrCreate(
                    ['nama' => 'rekening_'.$jurusan, 'tahun' => $tahunKontinu],
                    ['nilai' => $tertinggi]
                );
            });
    }

    protected function buatPetugas(): void
    {
        $petugas = [
            ['username' => 'admin', 'email' => 'admin@bankmini.test', 'nama_petugas' => 'Administrator Bank', 'role' => 'admin', 'password' => 'Admin@2026'],
            ['username' => 'supervisor', 'email' => 'supervisor@bankmini.test', 'nama_petugas' => 'Rina Puspita', 'role' => 'supervisor', 'password' => 'Super@2026'],
            ['username' => 'teller1', 'email' => 'teller1@bankmini.test', 'nama_petugas' => 'Dedi Kurniawan', 'role' => 'teller', 'password' => 'Teller@2026'],
        ];

        foreach ($petugas as $data) {
            User::firstOrCreate(
                ['username' => $data['username']],
                $data + ['status' => 'active'],
            );
        }
    }

    protected function createNasabah(array $data): void
    {
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'nama_petugas' => $data['nama_petugas'],
            'role' => 'nasabah',
            'status' => 'active',
        ]);

        Nasabah::create([
            'user_id' => $user->id,
            'no_rekening' => $data['no_rekening'],
            'kode_jurusan' => $data['kode_jurusan'] ?? KodeJurusan::dariKelas($data['kelas']),
            'qr_code' => $data['no_rekening'],
            'nis' => $data['nis'],
            'nama_siswa' => $data['nama_siswa'],
            'kelas' => $data['kelas'],
            'produk' => $data['produk'] ?? 'siswa',
            'pin_keamanan' => Hash::make($data['pin_keamanan']),
            'saldo' => $data['saldo'],
        ]);
    }
}
