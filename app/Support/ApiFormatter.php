<?php

namespace App\Support;

use App\Models\Konfigurasi;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiFormatter
{
    /**
     * Arah aliran dana sebuah transaksi.
     * Koreksi selalu berlawanan dengan transaksi asal yang dikoreksi.
     */
    public static function arah(Transaksi $transaksi): string
    {
        return match ($transaksi->jenis_trans) {
            Transaksi::JENIS_SETORAN => 'masuk',
            Transaksi::JENIS_KOREKSI_PENARIKAN => 'masuk',
            default => 'keluar',
        };
    }

    public static function nasabah(Nasabah $nasabah): array
    {
        return [
            'id' => $nasabah->id,
            'no_rekening' => $nasabah->no_rekening,
            'kode_jurusan' => $nasabah->kode_jurusan,
            'nis' => $nasabah->nis,
            'nama_siswa' => $nasabah->nama_siswa,
            'kelas' => $nasabah->kelas,
            'produk' => $nasabah->produk,
            'produk_label' => $nasabah->produk_label,
            'status' => $nasabah->status,
            'saldo' => (int) $nasabah->saldo,
            'saldo_rupiah' => $nasabah->saldo_rupiah,
            'saldo_minimum' => Konfigurasi::saldoMinimum(),
            'terdaftar_pada' => optional($nasabah->created_at)->toDateString(),
        ];
    }

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'nama_petugas' => $user->nama_petugas,
            'role' => $user->role,
            'role_label' => $user->role_label,
            'status' => $user->status,
        ];
    }

    public static function transaksi(Transaksi $transaksi): array
    {
        return [
            'id' => $transaksi->id,
            'tanggal' => optional($transaksi->tanggal)->toDateString(),
            'waktu' => optional($transaksi->created_at)->toDateTimeString(),
            'jenis_trans' => $transaksi->jenis_trans,
            'jenis_label' => $transaksi->jenis_label,
            'arah' => self::arah($transaksi),
            'is_koreksi' => $transaksi->isKoreksi(),
            'nominal' => (int) $transaksi->nominal,
            'nominal_rupiah' => $transaksi->nominal_rupiah,
            'no_struk' => $transaksi->no_struk,
            'status' => $transaksi->status,
            'status_label' => $transaksi->status_label,
            'teller' => $transaksi->user?->nama_petugas,
        ];
    }

    public static function detailTransaksi(Transaksi $transaksi): array
    {
        $nasabah = $transaksi->nasabah;

        return array_merge(self::transaksi($transaksi), [
            'no_rekening' => $nasabah?->no_rekening,
            'nama_siswa' => $nasabah?->nama_siswa,
            'nis' => $nasabah?->nis,
            'kelas' => $nasabah?->kelas,
            'produk' => $nasabah?->produk,
            'produk_label' => $nasabah?->produk_label,
            'saldo_sebelum' => (int) $transaksi->saldo_sebelum,
            'saldo_sesudah' => (int) $transaksi->saldo_sesudah,
            'qr_content' => self::qrContent($transaksi),
            'sekolah' => [
                'nama' => InfoBank::NAMA,
                'jenis' => InfoBank::JENIS,
                'tagline' => InfoBank::TAGLINE,
                'alamat' => InfoBank::ALAMAT,
                'lembaga' => InfoBank::LEMBAGA,
                'keterangan' => InfoBank::KETERANGAN,
            ],
            'catatan' => $transaksi->catatan,
            'konfirmasi_h1' => (bool) $transaksi->konfirmasi_h1,
            'dikoreksi' => (bool) $transaksi->dikoreksi,
        ]);
    }

    /**
     * Isi QR Code struk transaksi — dipakai endpoint API dan tampilan struk web.
     */
    public static function qrContent(Transaksi $transaksi): string
    {
        return implode('|', [
            'BANKMINI',
            $transaksi->no_struk ?: '-',
            $transaksi->nasabah?->no_rekening ?: '-',
            (int) $transaksi->nominal,
            $transaksi->tanggal?->format('Y-m-d') ?: '-',
            $transaksi->jam_transaksi,
        ]);
    }

    public static function notifikasi(UserNotification $notifikasi): array
    {
        return [
            'id' => $notifikasi->id,
            'tipe' => $notifikasi->tipe,
            'judul' => $notifikasi->judul,
            'pesan' => $notifikasi->pesan,
            'url' => $notifikasi->url,
            'sudah_dibaca' => $notifikasi->sudah_dibaca,
            'dibaca_pada' => optional($notifikasi->read_at)->toDateTimeString(),
            'dibuat_pada' => optional($notifikasi->created_at)->toDateTimeString(),
        ];
    }

    public static function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
