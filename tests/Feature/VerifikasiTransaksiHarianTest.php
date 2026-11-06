<?php

namespace Tests\Feature;

use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\VerifikasiHarian;
use App\Support\VerifikasiHarianProses;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiTransaksiHarianTest extends TestCase
{
    use RefreshDatabase;

    protected User $teller;

    protected User $supervisor;

    protected Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);

        $this->teller = User::where('username', 'teller1')->firstOrFail();
        $this->supervisor = User::where('role', 'supervisor')->firstOrFail();
        $this->nasabah = Nasabah::where('nis', '20241001')->firstOrFail();
    }

    protected function buatTransaksi(string $tanggal, string $jenis, int $nominal, string $struk): Transaksi
    {
        return Transaksi::create([
            'id_nasabah' => $this->nasabah->id,
            'id_user' => $this->teller->id,
            'tanggal' => $tanggal,
            'jenis_trans' => $jenis,
            'nominal' => $nominal,
            'no_struk' => $struk,
            'status' => Transaksi::STATUS_APPROVED,
        ]);
    }

    public function test_transaksi_dikelompokkan_berdasarkan_tanggal(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');
        $this->buatTransaksi('2026-09-01', 'penarikan', 5000, 'H-002');
        $this->buatTransaksi('2026-09-02', 'setoran', 20000, 'H-003');

        $response = $this->actingAs($this->supervisor)->get(route('verifikasi.index'));
        $response->assertOk();
        $response->assertSee('01 Sep 2026');
        $response->assertSee('02 Sep 2026');
    }

    public function test_supervisor_memilih_tanggal_dan_melihat_transaksi_belum_diverifikasi(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');
        $this->buatTransaksi('2026-09-01', 'penarikan', 5000, 'H-002');

        $response = $this->actingAs($this->supervisor)
            ->get(route('verifikasi.index', ['tanggal' => '2026-09-01']));

        $response->assertOk();
        $response->assertSee('H-001');
        $response->assertSee('H-002');
        $response->assertSee('Belum Diverifikasi');
    }

    public function test_ringkasan_harian_menampilkan_jumlah_setoran_penarikan_kas_dan_selisih(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 100000, 'H-001');
        $this->buatTransaksi('2026-09-01', 'setoran', 50000, 'H-002');
        $this->buatTransaksi('2026-09-01', 'penarikan', 30000, 'H-003');

        $response = $this->actingAs($this->supervisor)
            ->get(route('verifikasi.index', ['tanggal' => '2026-09-01']));

        // jumlah transaksi
        $response->assertSee('Jumlah Transaksi');
        // total setoran 150.000
        $response->assertSee('Rp 150.000');
        // total penarikan 30.000
        $response->assertSee('Rp 30.000');
        // total kas 120.000
        $response->assertSee('Rp 120.000');
        $response->assertSee('Total Kas');
        $response->assertSee('Selisih');
    }

    public function test_mekanisme_ada_selisih_dipakai_bila_laporan_teller_memiliki_selisih(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 100000, 'H-001');

        LaporanHarian::create([
            'teller_id' => $this->teller->id,
            'tanggal' => '2026-09-01',
            'saldo_awal_kas' => 0,
            'total_setoran' => 100000,
            'total_penarikan' => 0,
            'saldo_akhir_sistem' => 100000,
            'saldo_akhir_fisik' => 95000,
            'selisih' => -5000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->supervisor)->get(route('verifikasi.index'));

        $response->assertOk();
        $response->assertSee('Ada Selisih');
    }

    public function test_supervisor_dapat_memverifikasi_satu_tanggal(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        $response = $this->actingAs($this->supervisor)
            ->post(route('verifikasi.proses', '2026-09-01'), ['catatan' => 'Rekap sesuai']);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $v = VerifikasiHarian::whereDate('tanggal', '2026-09-01')->first();

        $this->assertNotNull($v);
        $this->assertSame(1, $v->jumlah_transaksi);
        $this->assertSame($this->supervisor->id, $v->verifikasi_oleh);
    }

    public function test_penolakan_wajib_memiliki_catatan_dan_mengirim_notifikasi_ke_teller(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        $response = $this->actingAs($this->supervisor)
            ->post(route('verifikasi.proses', '2026-09-01'), ['keputusan' => 'ditolak']);

        $response->assertSessionHasErrors('catatan');

        $this->actingAs($this->supervisor)
            ->post(route('verifikasi.proses', '2026-09-01'), [
                'keputusan' => 'ditolak',
                'catatan' => 'Bukti transaksi belum lengkap.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('verifikasi_harian', [
            'tanggal' => '2026-09-01',
            'status' => 'ditolak',
            'catatan' => 'Bukti transaksi belum lengkap.',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->teller->id,
            'tipe' => 'danger',
            'judul' => 'Verifikasi transaksi ditolak',
        ]);
    }

    public function test_verifikasi_yang_ditolak_dapat_diterima_setelah_ditinjau_ulang(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        VerifikasiHarianProses::verifikasi('2026-09-01', $this->supervisor, 'Bukti belum lengkap.', 'ditolak');
        $hasil = VerifikasiHarianProses::verifikasi('2026-09-01', $this->supervisor, null, 'diterima');

        $this->assertSame('diterima', $hasil->status);
        $this->assertNull($hasil->catatan);
    }

    public function test_verifikasi_tidak_dapat_diulang(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        $this->actingAs($this->supervisor)->post(route('verifikasi.proses', '2026-09-01'));
        $this->assertNotNull(VerifikasiHarian::whereDate('tanggal', '2026-09-01')->first());

        // Percobaan kedua harus ditolak.
        $response = $this->actingAs($this->supervisor)
            ->post(route('verifikasi.proses', '2026-09-01'));

        $response->assertSessionHas('error');
        $this->assertSame(1, VerifikasiHarian::whereDate('tanggal', '2026-09-01')->count());
    }

    public function test_hanya_satu_pengguna_yang_berhasil_saat_dibuka_bersamaan(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        $pertama = VerifikasiHarianProses::verifikasi('2026-09-01', $this->supervisor);
        $this->assertNotNull($pertama->verifikasi_at);

        $admin = User::where('role', 'admin')->firstOrFail();

        $this->expectException(\RuntimeException::class);
        VerifikasiHarianProses::verifikasi('2026-09-01', $admin);
    }

    public function test_tanggal_waktu_dan_pengguna_verifikasi_tersimpan(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        $this->actingAs($this->supervisor)->post(route('verifikasi.proses', '2026-09-01'));

        $v = VerifikasiHarian::whereDate('tanggal', '2026-09-01')->firstOrFail();

        $this->assertNotNull($v->verifikasi_at);
        $this->assertSame($this->supervisor->id, $v->verifikasi_oleh);
        $this->assertSame($this->supervisor->nama_petugas, $v->verifikator->nama_petugas);
    }

    public function test_riwayat_verifikasi_hari_sebelumnya_tetap_bisa_dilihat(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        $this->actingAs($this->supervisor)->post(route('verifikasi.proses', '2026-09-01'));

        $response = $this->actingAs($this->supervisor)
            ->get(route('verifikasi.index', ['tanggal' => '2026-09-01']));

        $response->assertOk();
        $response->assertSee('Sudah Diverifikasi');
        $response->assertSee('Verifikasi ulang tidak diizinkan');
        $response->assertSee('H-001');
    }

    public function test_teller_tidak_bisa_membuka_halaman_verifikasi(): void
    {
        $this->actingAs($this->teller)->get(route('verifikasi.index'))->assertForbidden();
    }

    public function test_transaksi_teller_tetap_berjalan_normal(): void
    {
        $this->buatTransaksi('2026-09-01', 'setoran', 10000, 'H-001');

        // Proses verifikasi tidak mengubah status transaksi teller.
        $this->actingAs($this->supervisor)->post(route('verifikasi.proses', '2026-09-01'));

        $this->assertDatabaseHas('transaksi', [
            'no_struk' => 'H-001',
            'status' => Transaksi::STATUS_APPROVED,
        ]);
    }
}
