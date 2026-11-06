<?php

use App\Models\JurnalAkuntansi;
use App\Models\Konfigurasi;
use App\Models\KoreksiTransaksi;
use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\NomorSequence;
use App\Models\Transaksi;
use App\Models\User;
use App\Support\ExcelExport;
use App\Support\NomorGenerator;
use App\Support\XlsxWriter;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('applies saldo minimum from konfigurasi on penarikan', function () {
    $admin = User::where('username', 'admin')->first();
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();
    $nasabah->update(['saldo' => 20000]);

    $this->actingAs($admin)
        ->put('/admin/konfigurasi', [
            'saldo_minimum' => 100000,
            'batas_penarikan_besar' => 1000000,
            'kas_awal_harian' => 500000,
            'kode_jurusan' => implode(',', Konfigurasi::kodeJurusan()),
        ])->assertRedirect(route('admin.konfigurasi.index'));

    expect(Konfigurasi::saldoMinimum())->toBe(100000);

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'penarikan',
            'nominal' => 5000,
            'pin_keamanan' => '123456',
        ])->assertSessionHas('error');

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe(20000);
});

it('rejects besar penarikan without konfirmasi H-1', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241002')->first();
    $nasabah->update(['saldo' => 2000000]);

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'penarikan',
            'nominal' => 1500000,
            'pin_keamanan' => '654321',
        ])->assertSessionHas('error');

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe(2000000);
    expect(Transaksi::count())->toBe(0);
});

it('allows besar penarikan with konfirmasi H-1 and records it', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241002')->first();
    $nasabah->update(['saldo' => 2000000]);

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'penarikan',
            'nominal' => 1500000,
            'pin_keamanan' => '654321',
            'konfirmasi_h1' => '1',
            'catatan' => 'Sudah konfirmasi ibu nasabah',
        ])->assertRedirect();

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe(500000);

    $transaksi = Transaksi::first();
    expect($transaksi->jenis_trans)->toBe('penarikan');
    expect($transaksi->konfirmasi_h1)->toBeTrue();
    expect($transaksi->catatan)->toBe('Sudah konfirmasi ibu nasabah');
    expect(JurnalAkuntansi::count())->toBe(2);
});

it('rejects transaksi for archived nasabah', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();
    $nasabah->update(['saldo' => 20000, 'arsip' => true]);

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'pin_keamanan' => '123456',
        ])->assertSessionHas('error');

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe(20000);
    expect(Transaksi::count())->toBe(0);
});

it('records logged-in teller on transaksi and laporan', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'pin_keamanan' => '123456',
        ])->assertRedirect();

    $transaksi = Transaksi::first();
    expect($transaksi->id_user)->toBe($teller->id);
    expect($transaksi->user?->nama_petugas)->toBe($teller->nama_petugas);

    $this->actingAs($teller)
        ->post('/teller/laporan', [
            'saldo_akhir_fisik' => 50000,
        ])->assertRedirect();

    $laporan = LaporanHarian::where('teller_id', $teller->id)->first();
    expect($laporan->teller_id)->toBe($teller->id);
    expect($laporan->total_setoran)->toBe(50000);
});

it('no longer exposes CRUD page /teller/subteller', function () {
    $teller = User::where('username', 'teller1')->first();

    $this->actingAs($teller)
        ->get('/teller/subteller')
        ->assertNotFound();
});

it('keeps nomor rekening sequence continuous across "years"', function () {
    $nasabahUji = User::create([
        'username' => 'ujisekolah1',
        'email' => null,
        'password' => 'Nasabah@2026',
        'nama_petugas' => 'Siswa Uji',
        'role' => 'nasabah',
        'status' => 'active',
    ]);

    $rekeningBaru = NomorGenerator::rekening('RPL');

    expect($rekeningBaru)->toMatch('/^RPL-\d{3}$/');
    expect(Nasabah::where('no_rekening', $rekeningBaru)->exists())->toBeFalse();

    $tertinggi = Nasabah::where('kode_jurusan', 'RPL')->get()
        ->map(fn ($n) => (int) preg_replace('/\D+/', '', (string) substr((string) $n->no_rekening, 3)))
        ->max();

    expect((int) preg_replace('/\D+/', '', (string) substr($rekeningBaru, 3)))->toBeGreaterThan($tertinggi);

    $nasabahBaru = Nasabah::create([
        'user_id' => $nasabahUji->id,
        'no_rekening' => $rekeningBaru,
        'kode_jurusan' => 'RPL',
        'qr_code' => $rekeningBaru,
        'nis' => 'TEST-JURUSAN',
        'nama_siswa' => 'Siswa Uji',
        'kelas' => 'RPL 1',
        'produk' => 'siswa',
        'pin_keamanan' => Hash::make('123456'),
        'saldo' => 0,
    ]);

    $kedua = NomorGenerator::rekening('RPL');
    expect($kedua)->not->toBe($rekeningBaru);
    expect((int) substr($kedua, -3))->toBe((int) substr($rekeningBaru, -3) + 1);

    $seq = NomorSequence::where('nama', 'rekening_RPL')->where('tahun', NomorGenerator::TAHUN_KONTINU)->first();
    expect($seq)->not->toBeNull();
    expect((int) $seq->nilai)->toBe((int) substr($kedua, -3));
});

it('rejects nomor rekening for undaftar jurusan', function () {
    expect(fn () => NomorGenerator::rekening('XXX'))
        ->toThrow(InvalidArgumentException::class);
});

it('records koreksi nominal and applies it on approval', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();
    $saldoAwal = $nasabah->saldo;

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'pin_keamanan' => '123456',
        ])->assertRedirect();

    $transaksiAsli = Transaksi::first();
    expect($nasabah->refresh()->saldo)->toBe($saldoAwal + 50000);

    $this->actingAs($teller)
        ->post('/teller/koreksi', [
            'transaksi_id' => $transaksiAsli->id,
            'nominal' => 30000,
            'alasan' => 'Nominal salah input',
        ])->assertRedirect(route('teller.koreksi.index'));

    $koreksi = KoreksiTransaksi::first();
    expect($koreksi->nominal)->toBe(30000);

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/approve")
        ->assertRedirect(route('supervisor.pusat-verifikasi', ['tab' => 'koreksi', 'status' => 'menunggu_approval']));

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe($saldoAwal + 50000 - 30000);

    $pembalik = Transaksi::where('koreksi_id', $koreksi->id)->first();
    expect($pembalik)->not->toBeNull();
    expect((int) $pembalik->nominal)->toBe(30000);
    expect($pembalik->jenis_trans)->toBe('penarikan');
    expect($transaksiAsli->refresh()->dikoreksi)->toBe(1);
});

it('does not change saldo when koreksi is rejected', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();
    $saldoAwal = $nasabah->saldo;

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'pin_keamanan' => '123456',
        ])->assertRedirect();

    $transaksiAsli = Transaksi::first();
    expect($nasabah->refresh()->saldo)->toBe($saldoAwal + 50000);

    $this->actingAs($teller)
        ->post('/teller/koreksi', [
            'transaksi_id' => $transaksiAsli->id,
            'nominal' => 30000,
            'alasan' => 'Nominal salah input',
        ])->assertRedirect();

    $koreksi = KoreksiTransaksi::first();

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/tolak", [
            'catatan_approver' => 'Data sudah benar',
        ])->assertRedirect(route('supervisor.pusat-verifikasi', ['tab' => 'koreksi', 'status' => 'menunggu_approval']));

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe($saldoAwal + 50000);
    expect($koreksi->refresh()->status)->toBe('ditolak');
    expect($transaksiAsli->refresh()->dikoreksi)->toBe(0);
});

it('only counts approved transaksi in laporan harian', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'setoran',
            'nominal' => 100000,
            'pin_keamanan' => '123456',
        ])->assertRedirect();

    Transaksi::create([
        'id_nasabah' => $nasabah->id,
        'id_user' => $teller->id,
        'tanggal' => today(),
        'jenis_trans' => 'setoran',
        'nominal' => 50000,
        'no_struk' => 'TRX-PENDING-TEST',
        'status' => 'pending',
    ]);

    $this->actingAs($teller)
        ->post('/teller/laporan', [
            'saldo_akhir_fisik' => 100000,
        ])->assertRedirect();

    $laporan = LaporanHarian::where('teller_id', $teller->id)->first();
    expect((int) $laporan->total_setoran)->toBe(100000);
    expect((int) $laporan->saldo_akhir_sistem)->toBe(100000);
});

it('exposes no registrasi route', function () {
    $this->get('/register')->assertNotFound();
});

it('rate limits api login', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/login', [
            'username' => 'salah',
            'password' => 'salah123',
        ])->assertStatus(422);
    }

    $this->postJson('/api/login', [
        'username' => 'salah',
        'password' => 'salah123',
    ])->assertStatus(429);
});

it('blocks inactive user from api', function () {
    $nasabah = User::where('username', '20241001')->first();
    $nasabah->update(['status' => 'nonaktif']);

    Sanctum::actingAs($nasabah);

    $this->getJson('/api/me')->assertForbidden();
});

it('generates valid xlsx export binary', function () {
    $bin = XlsxWriter::fromArray(['Nama', 'Nominal'], [['Andi', 50000]]);

    expect(substr(bin2hex(substr($bin, 0, 4)), 0, 8))->toBe('504b0304');

    $pos = 0;
    $isi = [];

    while ($pos + 30 <= strlen($bin)) {
        $sig = unpack('V', substr($bin, $pos, 4))[1];

        if ($sig !== 0x04034B50) {
            break;
        }

        $nameLen = unpack('v', substr($bin, $pos + 26, 2))[1];
        $extraLen = unpack('v', substr($bin, $pos + 28, 2))[1];
        $crc = unpack('V', substr($bin, $pos + 14, 4))[1];
        $comp = unpack('V', substr($bin, $pos + 18, 4))[1];

        $nama = substr($bin, $pos + 30, $nameLen);
        $data = substr($bin, $pos + 30 + $nameLen + $extraLen, $comp);

        if (crc32(gzinflate($data)) === $crc) {
            $isi[$nama] = gzinflate($data);
        }

        $pos += 30 + $nameLen + $extraLen + $comp;
    }

    expect($isi)->toHaveKey('[Content_Types].xml');
    expect($isi)->toHaveKey('xl/workbook.xml');
    expect($isi)->toHaveKey('xl/worksheets/sheet1.xml');
    expect($isi['xl/worksheets/sheet1.xml'])->toContain('Andi');
    expect($isi['[Content_Types].xml'])->toContain('openxmlformats');

    $eocdHex = substr(bin2hex(substr($bin, -22, 4)), 0, 8);
    expect($eocdHex)->toBe('504b0506');
});

it('serves jurnal report as downloadable xlsx for admin', function () {
    $admin = User::where('username', 'admin')->first();
    $teller = User::where('username', 'teller1')->first();

    $query = [
        'format' => 'excel',
        'mulai' => now()->startOfMonth()->format('Y-m-d'),
        'sampai' => now()->format('Y-m-d'),
        'teller' => $teller->id,
    ];

    $response = $this->actingAs($admin)
        ->get('/supervisor/laporan/jurnal?'.http_build_query($query));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))
        ->toBe(ExcelExport::MIME);
    expect($response->headers->get('Content-Disposition'))->toContain('jurnal-akuntansi');
    expect(substr(bin2hex(substr($response->getContent(), 0, 4)), 0, 8))->toBe('504b0304');
});

it('serves supervisor jurnal report as downloadable xlsx', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller = User::where('username', 'teller1')->first();

    $query = [
        'format' => 'excel',
        'mulai' => now()->startOfMonth()->format('Y-m-d'),
        'sampai' => now()->format('Y-m-d'),
        'teller' => $teller->id,
    ];

    $response = $this->actingAs($supervisor)
        ->get('/supervisor/laporan/jurnal?'.http_build_query($query));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))
        ->toBe(ExcelExport::MIME);
    expect($response->headers->get('Content-Disposition'))->toContain('jurnal-akuntansi');
    expect(substr(bin2hex(substr($response->getContent(), 0, 4)), 0, 8))->toBe('504b0304');
});
