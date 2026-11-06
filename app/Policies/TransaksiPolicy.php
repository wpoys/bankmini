<?php

namespace App\Policies;

use App\Models\Transaksi;
use App\Models\User;

/**
 * Otorisasi akses struk transaksi.
 *
 * - Teller    : hanya transaksi yang diprosesnya sendiri (lihat, cetak, unduh PDF).
 * - Supervisor: seluruh transaksi (lihat & cetak ulang).
 * - Nasabah  : hanya transaksi atas rekeningnya sendiri.
 * - Admin     : akses penuh sebagai pengelola sistem.
 *
 * methods dipanggil otomatis oleh $this->authorize()/Gate::allows() pada
 * controller dan attribute helpers di model Transaksi.
 */
class TransaksiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isNasabah();
    }

    /**
     * Melihat struk di layar.
     */
    public function viewStruk(User $user, Transaksi $transaksi): bool
    {
        return $this->bolehAkses($user, $transaksi);
    }

    /**
     * Halaman cetak khusus (blade print).
     */
    public function cetakStruk(User $user, Transaksi $transaksi): bool
    {
        return $this->bolehAkses($user, $transaksi);
    }

    /**
     * Unduh PDF struk.
     */
    public function unduhStruk(User $user, Transaksi $transaksi): bool
    {
        return $this->bolehAkses($user, $transaksi);
    }

    /**
     * Mencetak ulang struk oleh petugas (teller / supervisor).
     */
    public function cetakUlang(User $user, Transaksi $transaksi): bool
    {
        return $user->isSupervisor() || $user->isTeller();
    }

    protected function bolehAkses(User $user, Transaksi $transaksi): bool
    {
        if ($transaksi->status !== Transaksi::STATUS_APPROVED) {
            return false;
        }

        if ($user->isAdmin() || $user->isSupervisor()) {
            return true;
        }

        if ($user->isTeller()) {
            return (int) $transaksi->id_user === (int) $user->id;
        }

        if ($user->isNasabah()) {
            $nasabah = $user->nasabah()->first();

            return $nasabah !== null && (int) $transaksi->id_nasabah === (int) $nasabah->id;
        }

        return false;
    }
}
