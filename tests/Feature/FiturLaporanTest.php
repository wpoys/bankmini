<?php

use App\Models\KoreksiTransaksi;
use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('menolak penarikan yang menyisakan saldo di bawah saldo mengendap', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $this->actingAs($teller)->post('/teller/transaksi/proses', [
        'id_nasabah' => $nasabah->id,
        'jenis_trans' => 'setoran',
        'nominal' => 20000,
        'pin_keamanan' => '123456',
    ])->assertRedirect();

    $this->actingAs($teller)->post('/teller/transaksi/proses', [
        'id_nasabah' => $nasabah->id,
        'jenis_trans' => 'penarikan',
        'nominal' => 15000,
        'pin_keamanan' => '123456',
    ])->assertSessionHas('error');

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe(20000);
    expect(Transaksi::count())->toBe(1);
});

it('mengirim laporan berselisih lalu menahannya menjadi pending oleh supervisor', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $this->actingAs($teller)->post('/teller/transaksi/proses', [
        'id_nasabah' => $nasabah->id,
        'jenis_trans' => 'setoran',
        'nominal' => 100000,
        'pin_keamanan' => '123456',
    ])->assertRedirect();

    // Kas fisik tidak selaras: 90.000 sedangkan sistem 100.000
    $this->actingAs($teller)->post('/teller/laporan', [
        'saldo_akhir_fisik' => 90000,
        'pecahan' => [
            100000 => 0, 50000 => 1, 20000 => 2, 10000 => 0, 5000 => 0,
            2000 => 0, 1000 => 0, 500 => 0, 100 => 0,
        ],
    ])->assertRedirect();

    $laporan = LaporanHarian::where('teller_id', $teller->id)->whereDate('tanggal', today())->first();
    expect($laporan->selisih)->toBe(-10000);
    expect($laporan->status)->toBe('draft');

    // Rincian pecahan tersimpan pada rekonsiliasi kas
    expect($laporan->rekonsiliasi)->not->toBeNull();
    expect($laporan->rekonsiliasi->total_fisik)->toBe(90000);
    expect($laporan->rekonsiliasi->status)->toBe('selisih');
    expect($laporan->rekonsiliasi->detail)->toHaveCount(2);

    // Selisih tidak lagi memblokir pengiriman laporan
    $this->actingAs($teller)->post("/teller/laporan/{$laporan->id}/submit")
        ->assertSessionDoesntHaveErrors();

    $laporan->refresh();
    expect($laporan->status)->toBe('menunggu_approval');

    // Supervisor tidak boleh mengesahkan laporan berselisih
    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan->id}/approve", [
            'catatan_supervisor' => 'OK',
        ])->assertSessionHas('error');

    $laporan->refresh();
    expect($laporan->status)->toBe('menunggu_approval');

    // Menahan laporan wajib menyertakan catatan
    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan->id}/pending")
        ->assertSessionHasErrors('catatan_supervisor');

    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan->id}/pending", [
            'catatan_supervisor' => 'Selisih Rp 10.000, perbaiki rekonsiliasi.',
        ])->assertRedirect(route('supervisor.verifikasi.index'));

    $laporan->refresh();
    expect($laporan->status)->toBe('pending');

    // Teller memperbaiki kas fisik (dari status pending) lalu kirim ulang
    $this->actingAs($teller)->post('/teller/laporan', [
        'saldo_akhir_fisik' => 100000,
        'pecahan' => [
            100000 => 1, 50000 => 0, 20000 => 0, 10000 => 0, 5000 => 0,
            2000 => 0, 1000 => 0, 500 => 0, 100 => 0,
        ],
    ])->assertRedirect();

    $laporan->refresh();
    expect($laporan->selisih)->toBe(0);
    expect($laporan->rekonsiliasi->detail)->toHaveCount(1);

    $this->actingAs($teller)->post("/teller/laporan/{$laporan->id}/submit")
        ->assertSessionDoesntHaveErrors();

    $laporan->refresh();
    expect($laporan->status)->toBe('menunggu_approval');

    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan->id}/approve", [
            'catatan_supervisor' => 'Sesuai.',
        ])->assertRedirect(route('supervisor.verifikasi.index'));

    $laporan->refresh();
    expect($laporan->status)->toBe('disetujui');
});

it('mencatat pemindahan kas ke brankas saat laporan disetujui', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();

    $laporan = LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today(),
        'saldo_awal_kas' => 0,
        'total_setoran' => 250000,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 250000,
        'status' => 'menunggu_approval',
        'kas_dipindahkan' => 250000,
        'submitted_at' => now(),
    ]);

    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan->id}/approve", [
            'catatan_supervisor' => 'OK',
        ])->assertRedirect(route('supervisor.verifikasi.index'));

    $laporan->refresh();
    expect($laporan->status)->toBe('disetujui');
    expect($laporan->kas_dipindahkan)->toBe(250000);
    expect($laporan->kas_pindah_brankas)->toBeTrue();

    expect(DB::table('jurnal_akuntansi')
        ->where('kode_akun', 'Brankas_102')->where('posisi', 'debit')->whereNull('id_transaksi')->count())->toBe(1);
    expect(DB::table('jurnal_akuntansi')
        ->where('kode_akun', 'Kas_101')->where('posisi', 'kredit')->whereNull('id_transaksi')->count())->toBe(1);
});

it('koreksi transaksi membuat transaksi pembalik saat disetujui', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $this->actingAs($teller)->post('/teller/transaksi/proses', [
        'id_nasabah' => $nasabah->id,
        'jenis_trans' => 'setoran',
        'nominal' => 50000,
        'pin_keamanan' => '123456',
    ])->assertRedirect();

    $transaksiAsli = Transaksi::first();
    expect($nasabah->fresh()->saldo)->toBe(50000);

    $this->actingAs($teller)->post('/teller/koreksi', [
        'transaksi_id' => $transaksiAsli->id,
        'alasan' => 'Setoran ganda',
    ])->assertRedirect(route('teller.koreksi.index'));

    $koreksi = KoreksiTransaksi::where('transaksi_id', $transaksiAsli->id)->first();
    expect($koreksi->status)->toBe('menunggu_approval');

    $jurnalSebelum = DB::table('jurnal_akuntansi')->count();

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/approve")
        ->assertRedirect(route('supervisor.pusat-verifikasi', ['tab' => 'koreksi', 'status' => 'menunggu_approval']));

    $koreksi->refresh();
    expect($koreksi->status)->toBe('disetujui');

    $transaksiAsli->refresh();
    expect($transaksiAsli->dikoreksi)->toBeTruthy();

    $pembalik = Transaksi::where('koreksi_id', $koreksi->id)->first();
    expect($pembalik)->not->toBeNull();
    expect($pembalik->jenis_trans)->toBe('penarikan');
    expect($pembalik->nominal)->toBe(50000);
    expect($nasabah->fresh()->saldo)->toBe(0);
    expect(DB::table('jurnal_akuntansi')->count())->toBe($jurnalSebelum + 2);
});

it('menampilkan rekapitulasi periode dan rincian lembaran uang fisik pada laporan kas harian', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller = User::where('username', 'teller1')->first();
    $hariPertama = now()->subDay();
    $hariKedua = now();

    $laporanPertama = LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => $hariPertama->toDateString(),
        'saldo_awal_kas' => 500000,
        'total_setoran' => 100000,
        'total_penarikan' => 20000,
        'saldo_akhir_sistem' => 580000,
        'saldo_akhir_fisik' => 580000,
        'selisih' => 0,
        'status' => 'disetujui',
    ]);

    $rekonsiliasiPertama = $laporanPertama->rekonsiliasi()->create([
        'teller_id' => $teller->id,
        'total_fisik' => 580000,
        'selisih' => 0,
        'status' => 'cocok',
    ]);

    foreach ([100000 => 5, 50000 => 1, 20000 => 1, 10000 => 1] as $pecahan => $jumlah) {
        $rekonsiliasiPertama->detail()->create([
            'pecahan' => $pecahan,
            'jumlah_lembar' => $jumlah,
            'subtotal' => $pecahan * $jumlah,
        ]);
    }

    LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => $hariKedua->toDateString(),
        'saldo_awal_kas' => 580000,
        'total_setoran' => 50000,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 630000,
        'saldo_akhir_fisik' => 630000,
        'selisih' => 0,
        'status' => 'menunggu_approval',
    ])->rekonsiliasi()->create([
        'teller_id' => $teller->id,
        'total_fisik' => 630000,
        'selisih' => 0,
        'status' => 'cocok',
    ])->detail()->createMany([
        ['pecahan' => 100000, 'jumlah_lembar' => 6, 'subtotal' => 600000],
        ['pecahan' => 20000, 'jumlah_lembar' => 1, 'subtotal' => 20000],
        ['pecahan' => 10000, 'jumlah_lembar' => 1, 'subtotal' => 10000],
    ]);

    $url = '/supervisor/laporan/laporan-harian?mulai='.$hariPertama->toDateString().'&sampai='.$hariKedua->toDateString();

    $this->actingAs($supervisor)
        ->get($url)
        ->assertOk()
        ->assertSee('Rekapitulasi Periode')
        ->assertSee('Rincian Lembaran: Uang Fisik')
        ->assertSee('TOTAL SALDO AWAL PERIODE')
        ->assertSee('Saldo awal periode')
        ->assertSee('Saldo akhir sistem periode')
        ->assertSee('Rp 500.000')
        ->assertSee('Rp 150.000')
        ->assertSee('Rp 20.000')
        ->assertSee('Rp 630.000')
        ->assertSee('Rincian Kas Harian')
        ->assertSee('Buku Besar / Rincian Transaksi');

    $pdf = $this->actingAs($supervisor)->get($url.'&format=pdf');
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});

it('memfilter laporan harian berdasarkan teller terpilih', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller1 = User::where('username', 'teller1')->first();
    $teller2 = User::where('username', 'teller2')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    foreach ([[$teller1, 'FILTER-T1'], [$teller2, 'FILTER-T2']] as [$teller, $noStruk]) {
        Transaksi::create([
            'id_nasabah' => $nasabah->id,
            'id_user' => $teller->id,
            'tanggal' => today()->toDateString(),
            'jenis_trans' => 'setoran',
            'nominal' => 10000,
            'no_struk' => $noStruk,
            'status' => 'approved',
        ]);

        LaporanHarian::create([
            'teller_id' => $teller->id,
            'tanggal' => today()->toDateString(),
            'saldo_awal_kas' => 0,
            'total_setoran' => 10000,
            'total_penarikan' => 0,
            'saldo_akhir_sistem' => 10000,
            'saldo_akhir_fisik' => 10000,
            'selisih' => 0,
            'status' => 'menunggu_approval',
        ]);
    }

    $url = '/supervisor/laporan/laporan-harian?mulai='.today()->toDateString().'&sampai='.today()->toDateString();

    $this->actingAs($supervisor)
        ->get($url)
        ->assertOk()
        ->assertSee('FILTER-T1')
        ->assertSee('FILTER-T2');

    $this->actingAs($supervisor)
        ->get($url.'&teller='.$teller2->id)
        ->assertOk()
        ->assertSee('FILTER-T2')
        ->assertDontSee('FILTER-T1');

    $pdf = $this->actingAs($supervisor)->get($url.'&teller='.$teller2->id.'&format=pdf');
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');

    $excel = $this->actingAs($supervisor)->get($url.'&teller='.$teller2->id.'&format=excel');
    $excel->assertOk();
    expect($excel->headers->get('content-type'))->toContain('spreadsheetml.sheet');
});

it('tetap membuat pdf laporan periode ketika rincian uang fisik tidak tersedia', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller = User::where('username', 'teller1')->first();

    LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today()->toDateString(),
        'saldo_awal_kas' => 250000,
        'total_setoran' => 50000,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 300000,
        'saldo_akhir_fisik' => 300000,
        'selisih' => 0,
        'status' => 'draft',
    ]);

    $url = '/supervisor/laporan/laporan-harian?mulai='.today()->toDateString().'&sampai='.today()->toDateString();

    $this->actingAs($supervisor)
        ->get($url)
        ->assertOk()
        ->assertSee('Rincian Lembaran: Uang Fisik')
        ->assertSee('TOTAL SALDO AWAL PERIODE');

    $pdf = $this->actingAs($supervisor)->get($url.'&format=pdf');
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});

it('menolak koreksi dengan alasan wajib saat penolakan', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $this->actingAs($teller)->post('/teller/transaksi/proses', [
        'id_nasabah' => $nasabah->id,
        'jenis_trans' => 'setoran',
        'nominal' => 10000,
        'pin_keamanan' => '123456',
    ])->assertRedirect();

    $transaksiAsli = Transaksi::first();

    $this->actingAs($teller)->post('/teller/koreksi', [
        'transaksi_id' => $transaksiAsli->id,
        'alasan' => 'Salah nominal',
    ])->assertRedirect();

    $koreksi = KoreksiTransaksi::where('transaksi_id', $transaksiAsli->id)->first();

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/tolak")
        ->assertSessionHasErrors('catatan_approver');

    $koreksi->refresh();
    expect($koreksi->status)->toBe('menunggu_approval');

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/tolak", ['catatan_approver' => 'Barang sudah benar'])
        ->assertRedirect(route('supervisor.pusat-verifikasi', ['tab' => 'koreksi', 'status' => 'menunggu_approval']));

    $koreksi->refresh();
    expect($koreksi->status)->toBe('ditolak');
    expect($transaksiAsli->fresh()->dikoreksi)->toBe(0);
});

it('menerima koreksi tanpa transaksi asli (uang tidak layak edar / temuan)', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();
    $nasabah = Nasabah::where('nis', '20241002')->first();

    // Tanpa transaksi asli wajib memilih nasabah, jenis, dan nominal.
    $this->actingAs($teller)->post('/teller/koreksi', [
        'nasabah_id' => $nasabah->id,
        'jenis' => 'koreksi_setoran',
        'alasan' => 'Uang pecahan ditemukan, nominal kosong.',
    ])->assertSessionHasErrors('nominal');

    $this->actingAs($teller)->post('/teller/koreksi', [
        'nasabah_id' => $nasabah->id,
        'nominal' => 50000,
        'alasan' => 'Uang pecahan ditemukan, jenis kosong.',
    ])->assertSessionHasErrors('jenis');

    $saldoAwal = (int) $nasabah->saldo;

    $this->actingAs($teller)->post('/teller/koreksi', [
        'nasabah_id' => $nasabah->id,
        'jenis' => 'koreksi_setoran',
        'nominal' => 50000,
        'alasan' => 'Ditemukan uang pecahan 50.000 tidak layak edar di loket.',
    ])->assertRedirect(route('teller.koreksi.index'));

    $koreksi = KoreksiTransaksi::whereNull('transaksi_id')->first();
    expect($koreksi)->not->toBeNull();
    expect($koreksi->jenis)->toBe('koreksi_setoran');
    expect($koreksi->tanpa_transaksi_asli)->toBeTrue();

    // Disetujui oleh supervisor: transaksi koreksi baru dibuat, bukan pembalik.
    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/approve", [
            'catatan_approver' => 'Disetujui, uang tidak layak edar.',
        ])->assertRedirect(route('supervisor.pusat-verifikasi', ['tab' => 'koreksi', 'status' => 'menunggu_approval']));

    $koreksi->refresh();
    expect($koreksi->status)->toBe('disetujui');

    $transaksiBaru = Transaksi::where('koreksi_id', $koreksi->id)->first();
    expect($transaksiBaru)->not->toBeNull();
    expect($transaksiBaru->jenis_trans)->toBe('setoran');
    expect((int) $transaksiBaru->nominal)->toBe(50000);

    expect((int) $nasabah->fresh()->saldo)->toBe($saldoAwal + 50000);

    // Penarikan koreksi tanpa transaksi asli menolak saldo tak cukup.
    $this->actingAs($teller)->post('/teller/koreksi', [
        'nasabah_id' => $nasabah->id,
        'jenis' => 'koreksi_penarikan',
        'nominal' => 999999999,
        'alasan' => 'Koreksi penarikan melebihi saldo.',
    ])->assertRedirect(route('teller.koreksi.index'));

    $koreksiTarik = KoreksiTransaksi::whereNull('transaksi_id')
        ->where('jenis', 'koreksi_penarikan')
        ->orderByDesc('id')
        ->first();

    expect($koreksiTarik)->not->toBeNull();

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksiTarik->id}/approve")
        ->assertSessionHas('error');

    expect($koreksiTarik->refresh()->status)->toBe('menunggu_approval');
});
