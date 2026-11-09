<?php

use App\Http\Controllers\Accounting\JurnalController as AccountingJurnalController;
use App\Http\Controllers\Accounting\LaporanController as AccountingLaporanController;
use App\Http\Controllers\Accounting\VerifikasiKoreksiController as AccountingVerifikasiKoreksiController;
use App\Http\Controllers\Admin\BackupController as AdminBackupController;
use App\Http\Controllers\Admin\BrankasController as AdminBrankasController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KonfigurasiController as AdminKonfigurasiController;
use App\Http\Controllers\Admin\NasabahController as AdminNasabahController;
use App\Http\Controllers\Admin\PenggunaController as AdminPenggunaController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\JadwalPetugasController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PermintaanPinController;
use App\Http\Controllers\StrukController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Supervisor\LaporanController as SupervisorLaporanController;
use App\Http\Controllers\Supervisor\VerifikasiController as SupervisorVerifikasiController;
use App\Http\Controllers\Teller\DashboardController as TellerDashboardController;
use App\Http\Controllers\Teller\KoreksiController as TellerKoreksiController;
use App\Http\Controllers\Teller\LaporanController as TellerLaporanController;
use App\Http\Controllers\Teller\TransaksiController as TellerTransaksiController;
use App\Http\Controllers\VerifikasiHarianController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect(match (Auth::user()->role) {
            'admin' => route('admin.dashboard'),
            'teller' => route('teller.dashboard'),
            'supervisor' => route('supervisor.dashboard'),
            default => route('login'),
        });
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password/kirim-otp', [PasswordResetController::class, 'kirimOtp'])->middleware('throttle:3,1')->name('password.kirim-otp');
    Route::post('/forgot-password/validasi-otp', [PasswordResetController::class, 'validasiOtp'])->middleware('throttle:5,1')->name('password.validasi-otp');
    Route::get('/buat-password-baru', [PasswordResetController::class, 'showBuatPassword'])->name('password.buat-password');
    Route::post('/buat-password-baru', [PasswordResetController::class, 'simpanPassword'])->name('password.simpan');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'check.active'])->group(function () {
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
    Route::delete('/notifikasi/{notifikasi}', [NotifikasiController::class, 'hapus'])->name('notifikasi.hapus');

    Route::prefix('admin')->middleware('role:admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::put('brankas/saldo-awal', [AdminBrankasController::class, 'update'])->name('brankas.update');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('nasabah', [AdminNasabahController::class, 'index'])->name('nasabah.index');
        Route::get('nasabah/create', [AdminNasabahController::class, 'create'])->name('nasabah.create');
        Route::post('nasabah', [AdminNasabahController::class, 'store'])->name('nasabah.store');
        Route::get('nasabah/{nasabah}/edit', [AdminNasabahController::class, 'edit'])->name('nasabah.edit');
        Route::put('nasabah/{nasabah}', [AdminNasabahController::class, 'update'])->name('nasabah.update');
        Route::delete('nasabah/{nasabah}', [AdminNasabahController::class, 'destroy'])->name('nasabah.destroy');
        Route::post('nasabah/{nasabah}/arsip', [AdminNasabahController::class, 'arsip'])->name('nasabah.arsip');
        Route::post('nasabah/{nasabah}/restore', [AdminNasabahController::class, 'restore'])->name('nasabah.restore');

        Route::get('nasabah/import', [AdminNasabahController::class, 'importForm'])->name('nasabah.import');
        Route::post('nasabah/import', [AdminNasabahController::class, 'importPreview'])->name('nasabah.import.preview');
        Route::post('nasabah/import/konfirmasi', [AdminNasabahController::class, 'importKonfirmasi'])->name('nasabah.import.konfirmasi');
        Route::get('nasabah/import/hasil', [AdminNasabahController::class, 'importHasil'])->name('nasabah.import.hasil');
        Route::get('nasabah/import/template', [AdminNasabahController::class, 'downloadTemplate'])->name('nasabah.import.template');
        Route::get('nasabah/import/riwayat', [AdminNasabahController::class, 'importHistory'])->name('nasabah.import.history');
        Route::get('nasabah/import/riwayat/{batch}', [AdminNasabahController::class, 'importHistoryDetail'])->name('nasabah.import.history.show');

        Route::post('nasabah/permintaan-reset/{permintaan}/proses', [AdminNasabahController::class, 'prosesPermintaanResetPin'])->name('nasabah.reset-pin.proses');

        Route::get('backup', fn () => redirect()->route('admin.konfigurasi.index'))->name('backup.index');
        Route::post('backup', [AdminBackupController::class, 'store'])->name('backup.store');
        Route::get('backup/{file}/download', [AdminBackupController::class, 'download'])->name('backup.download');
        Route::delete('backup/{file}', [AdminBackupController::class, 'destroy'])->name('backup.destroy');

        Route::get('konfigurasi', [AdminKonfigurasiController::class, 'index'])->name('konfigurasi.index');
        Route::put('konfigurasi', [AdminKonfigurasiController::class, 'update'])->name('konfigurasi.update');

        Route::get('struk/{transaksi}', [StrukController::class, 'show'])->name('struk');
        Route::get('struk/{transaksi}/cetak', [StrukController::class, 'cetak'])->name('struk.cetak');
        Route::get('struk/{transaksi}/pdf', [StrukController::class, 'pdf'])->name('struk.pdf');

        Route::prefix('jadwal')->name('jadwal.')->group(function () {
            Route::get('/', [JadwalPetugasController::class, 'index'])->name('index');
            Route::get('create', [JadwalPetugasController::class, 'create'])->name('create');
            Route::post('/', [JadwalPetugasController::class, 'store'])->name('store');
            Route::get('{jadwal}/edit', [JadwalPetugasController::class, 'edit'])->name('edit');
            Route::put('{jadwal}', [JadwalPetugasController::class, 'update'])->name('update');
            Route::post('{jadwal}/batalkan', [JadwalPetugasController::class, 'batalkan'])->name('batalkan');
        });
    });

    Route::prefix('teller')->middleware('role:teller')->name('teller.')->group(function () {
        Route::get('/dashboard', [TellerDashboardController::class, 'index'])->name('dashboard');
        Route::post('brankas/ambil', [TellerDashboardController::class, 'ambilKas'])->name('brankas.ambil');
        Route::post('dashboard/cari-nasabah', [TellerDashboardController::class, 'cariNasabah'])->name('dashboard.cari-nasabah');

        Route::get('transaksi', [TellerTransaksiController::class, 'index'])->name('transaksi.index');
        Route::get('transaksi/form/{nasabah}', [TellerTransaksiController::class, 'formNasabah'])->name('transaksi.form');
        Route::get('transaksi/cari', [TellerTransaksiController::class, 'index'])->name('transaksi.cari.get');
        Route::post('transaksi/cari', [TellerTransaksiController::class, 'cariNasabah'])->name('transaksi.cari');
        Route::post('transaksi/proses', [TellerTransaksiController::class, 'proses'])->name('transaksi.proses');
        Route::get('transaksi/otorisasi/{transaksi}/tunggu', [TellerTransaksiController::class, 'tungguOtorisasi'])->name('transaksi.otorisasi.tunggu');
        Route::get('transaksi/otorisasi/{transaksi}/status', [TellerTransaksiController::class, 'statusOtorisasi'])->name('transaksi.otorisasi.status');

        Route::get('transaksi/struk/{transaksi}', [StrukController::class, 'show'])->name('struk');
        Route::get('transaksi/struk/{transaksi}/cetak', [StrukController::class, 'cetak'])->name('struk.cetak');
        Route::get('transaksi/struk/{transaksi}/pdf', [StrukController::class, 'pdf'])->name('struk.pdf');

        Route::get('laporan', [TellerLaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/create', [TellerLaporanController::class, 'create'])->name('laporan.create');
        Route::post('laporan', [TellerLaporanController::class, 'store'])->name('laporan.store');
        Route::get('laporan/{laporan}', [TellerLaporanController::class, 'show'])->name('laporan.show');
        Route::get('laporan/{laporan}/pdf', [TellerLaporanController::class, 'pdf'])->name('laporan.pdf');
        Route::post('laporan/{laporan}/submit', [TellerLaporanController::class, 'submit'])->name('laporan.submit');

        Route::prefix('jadwal')->name('jadwal.')->group(function () {
            Route::get('/', [JadwalPetugasController::class, 'index'])->name('index');
            Route::get('create', [JadwalPetugasController::class, 'create'])->name('create');
            Route::post('/', [JadwalPetugasController::class, 'store'])->name('store');
            Route::get('{jadwal}/edit', [JadwalPetugasController::class, 'edit'])->name('edit');
            Route::put('{jadwal}', [JadwalPetugasController::class, 'update'])->name('update');
            Route::post('{jadwal}/batalkan', [JadwalPetugasController::class, 'batalkan'])->name('batalkan');
        });

        Route::get('koreksi', [TellerKoreksiController::class, 'index'])->name('koreksi.index');
        Route::get('koreksi/transaksi/{nasabah}', [TellerKoreksiController::class, 'transaksiNasabah'])->name('koreksi.transaksi');
        Route::get('koreksi/create', [TellerKoreksiController::class, 'create'])->name('koreksi.create');
        Route::post('koreksi', [TellerKoreksiController::class, 'store'])->name('koreksi.store');
    });

    // Verifikasi transaksi per hari + pengajuan PIN: Admin dan Supervisor
    // memiliki hak yang sama (salah satu cukup memproses).
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('verifikasi-harian', [VerifikasiHarianController::class, 'index'])->name('verifikasi.index');
        Route::post('verifikasi-harian/{tanggal}', [VerifikasiHarianController::class, 'verifikasi'])->name('verifikasi.proses');

        Route::get('permintaan-pin', [PermintaanPinController::class, 'index'])->name('permintaan-pin.index');
        Route::post('permintaan-pin/{permintaan}/setujui', [PermintaanPinController::class, 'setujui'])->name('permintaan-pin.setujui');
        Route::post('permintaan-pin/{permintaan}/tolak', [PermintaanPinController::class, 'tolak'])->name('permintaan-pin.tolak');
    });

    // Admin dan Supervisor berbagi akses modul review laporan, jurnal,
    // verifikasi koreksi, dan ekspor laporan (sifatnya read-only untuk admin).
    Route::prefix('supervisor')->middleware('role:admin,supervisor')->name('supervisor.')->group(function () {
        Route::get('/dashboard', [SupervisorDashboardController::class, 'index'])->name('dashboard');
        Route::get('pusat-verifikasi', [SupervisorVerifikasiController::class, 'pusat'])->name('pusat-verifikasi');

        Route::get('verifikasi', [SupervisorVerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::get('verifikasi/{laporan}', [SupervisorVerifikasiController::class, 'show'])->name('verifikasi.show');
        Route::post('verifikasi/{laporan}/approve', [SupervisorVerifikasiController::class, 'approve'])->name('verifikasi.approve');
        Route::post('verifikasi/{laporan}/reject', [SupervisorVerifikasiController::class, 'reject'])->name('verifikasi.reject');
        Route::post('verifikasi/{laporan}/pending', [SupervisorVerifikasiController::class, 'pending'])->name('verifikasi.pending');

        Route::get('audit', [SupervisorVerifikasiController::class, 'audit'])->name('audit');

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/dashboard', [SupervisorLaporanController::class, 'dashboard'])->name('dashboard');
            Route::get('operasional', [SupervisorLaporanController::class, 'laporanOperasional'])->name('operasional');
            Route::get('laporan-harian', [SupervisorLaporanController::class, 'laporanHarian'])->name('laporan-harian');
            Route::get('buku-besar', [SupervisorLaporanController::class, 'bukuBesar'])->name('buku-besar');
            Route::get('jurnal', [AccountingJurnalController::class, 'index'])->name('jurnal');
            Route::get('mutasi-nasabah', [SupervisorLaporanController::class, 'mutasiNasabah'])->name('mutasi-nasabah');
            Route::get('saldo-produk', [SupervisorLaporanController::class, 'saldoProduk'])->name('saldo-produk');
            Route::get('laporan-tahunan', [SupervisorLaporanController::class, 'laporanTahunan'])->name('laporan-tahunan');
            Route::get('/panel', [AccountingLaporanController::class, 'panel'])->name('panel');
            Route::get('kartu-simpanan', [AccountingLaporanController::class, 'kartuSimpanan'])->name('kartu-simpanan');
        });

        Route::get('jurnal', [AccountingJurnalController::class, 'index'])->name('jurnal');

        Route::prefix('koreksi')->name('koreksi.')->group(function () {
            Route::post('{koreksi}/approve', [AccountingVerifikasiKoreksiController::class, 'approve'])->name('approve');
            Route::post('{koreksi}/tolak', [AccountingVerifikasiKoreksiController::class, 'tolak'])->name('tolak');
        });

        Route::get('jadwal', [JadwalPetugasController::class, 'index'])->name('jadwal.index');

        Route::get('struk/{transaksi}', [StrukController::class, 'show'])->name('struk');
        Route::get('struk/{transaksi}/cetak', [StrukController::class, 'cetak'])->name('struk.cetak');
        Route::get('struk/{transaksi}/pdf', [StrukController::class, 'pdf'])->name('struk.pdf');
    });
});

Route::prefix('admin')->middleware(['auth', 'check.active', 'role:admin'])->name('admin.')->group(function () {
    Route::get('pengguna', [AdminPenggunaController::class, 'index'])->name('pengguna.index');
    Route::post('pengguna/arsip-terpilih', [AdminPenggunaController::class, 'arsipTerpilih'])->name('pengguna.arsip-terpilih');
    Route::post('pengguna/pulihkan-terpilih', [AdminPenggunaController::class, 'pulihkanTerpilih'])->name('pengguna.pulihkan-terpilih');
    Route::post('pengguna/hapus-terpilih', [AdminPenggunaController::class, 'hapusTerpilih'])->name('pengguna.hapus-terpilih');
    Route::post('pengguna/ekspor/{format}', [AdminPenggunaController::class, 'ekspor'])->name('pengguna.ekspor');
});
