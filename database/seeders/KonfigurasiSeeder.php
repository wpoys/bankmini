<?php

namespace Database\Seeders;

use App\Models\Konfigurasi;
use Illuminate\Database\Seeder;

class KonfigurasiSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            Konfigurasi::SALDO_MINIMUM => '10000',
            Konfigurasi::BATAS_PENARIKAN_BESAR => '1000000',
            Konfigurasi::KAS_AWAL_HARIAN => '500000',
            Konfigurasi::KODE_JURUSAN => implode(',', Konfigurasi::KODE_JURUSAN_DEFAULT),
            Konfigurasi::NAMA_SEKOLAH => 'SMK Negeri 1 Bantul',
            Konfigurasi::ALAMAT_SEKOLAH => 'Jl. Pendidikan No. 1, Kabupaten Bantul, DI Yogyakarta',
            Konfigurasi::KOTA_SEKOLAH => 'Bantul',
            Konfigurasi::JAM_OPERASIONAL => 'Senin s/d Jumat 08.00 - 14.30 WIB',
            Konfigurasi::LOKASI_LOKET => 'Loket Kasir Bank Mini, Gedung Utama Lantai 1',
        ];

        foreach ($values as $key => $value) {
            Konfigurasi::set($key, $value, Konfigurasi::DESKRIPSI[$key] ?? null);
        }
    }
}
