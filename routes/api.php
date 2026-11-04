<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AktivasiController;
use App\Http\Controllers\Api\NasabahController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::prefix('aktivasi')->middleware('throttle:10,1')->group(function () {
    Route::post('/minta-kode', [AktivasiController::class, 'mintaKode']);
    Route::post('/verifikasi-kode', [AktivasiController::class, 'verifikasiKode']);
    Route::post('/kirim-ulang', [AktivasiController::class, 'kirimUlang']);
    Route::post('/kredensial', [AktivasiController::class, 'simpanKredensial']);
});

Route::prefix('lupa-password')->group(function () {
    Route::post('/kirim-otp', [PasswordResetController::class, 'kirimOtp'])->middleware('throttle:3,1');
    Route::post('/validasi-otp', [PasswordResetController::class, 'validasiOtp'])->middleware('throttle:5,1');
    Route::post('/simpan-password', [PasswordResetController::class, 'simpanPassword'])->middleware('throttle:5,1');
});

Route::middleware(['auth:sanctum', 'check.active.api'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('nasabah')->group(function () {
        Route::get('/dashboard', [NasabahController::class, 'dashboard']);
        Route::get('/saldo', [NasabahController::class, 'saldo']);
        Route::get('/profil', [NasabahController::class, 'profil']);
        Route::post('/ubah-pin', [NasabahController::class, 'ubahPin'])->middleware('throttle:5,1');
        Route::get('/pengajuan-pin', [NasabahController::class, 'statusPengajuanPin']);
        Route::get('/transaksi', [NasabahController::class, 'transaksi']);
        Route::get('/transaksi/cetak', [NasabahController::class, 'cetakMutasi']);
        Route::get('/transaksi/{transaksi}', [NasabahController::class, 'detailTransaksi'])
            ->whereNumber('transaksi');
    });

    Route::prefix('notifikasi')->group(function () {
        Route::get('/', [NotifikasiController::class, 'index']);
        Route::get('/belum-dibaca', [NotifikasiController::class, 'unreadCount']);
        Route::post('/baca-semua', [NotifikasiController::class, 'bacaSemua']);
        Route::post('/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->whereNumber('notifikasi');
        Route::delete('/{notifikasi}', [NotifikasiController::class, 'hapus'])->whereNumber('notifikasi');
    });
});
