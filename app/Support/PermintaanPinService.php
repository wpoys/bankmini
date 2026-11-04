<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Proses pengajuan perubahan PIN nasabah.
 *
 * KEAMANAN PIN:
 * - PIN baru disimpan sebagai hash (pin_baru_hash), TIDAK PERNAH plaintext.
 * - Admin & Supervisor tidak dapat melihat PIN baru/lama di halaman, tabel,
 *   modal, response API, log, maupun notifikasi.
 * - Keduanya tidak perlu mengetik PIN baru saat menyetujui; cukup menyetujui,
 *   sistem otomatis menerapkan hash yang sudah disimpan nasabah.
 */
class PermintaanPinService
{
    /**
     * Nasabah mengajukan PIN baru. Wajib menyertakan PIN lama sebagai bukti
     * kepemilikan. PIN baru langsung di-hash, tidak pernah disimpan polos.
     */
    public static function ajukan(Nasabah $nasabah, string $pinLama, string $pinBaru): PermintaanResetPin
    {
        if (! $nasabah->pin_keamanan || ! Hash::check($pinLama, $nasabah->pin_keamanan)) {
            throw new \InvalidArgumentException('PIN lama tidak sesuai.');
        }

        if ($pinBaru === $pinLama) {
            throw new \InvalidArgumentException('PIN baru harus berbeda dari PIN lama.');
        }

        if (Hash::check($pinBaru, $nasabah->pin_keamanan)) {
            throw new \InvalidArgumentException('PIN baru tidak boleh sama dengan PIN lama.');
        }

        if (static::pinLemah($pinBaru)) {
            throw new \InvalidArgumentException('PIN baru terlalu lemah. Hindari angka berulang atau berurutan.');
        }

        // Hanya satu pengajuan menunggu pada satu waktu.
        $pending = PermintaanResetPin::where('id_nasabah', $nasabah->id)
            ->where('status', PermintaanResetPin::STATUS_PENDING)
            ->first();

        if ($pending) {
            throw new \RuntimeException('Anda sudah memiliki pengajuan yang masih menunggu persetujuan.');
        }

        return DB::transaction(function () use ($nasabah, $pinBaru) {
            $permintaan = PermintaanResetPin::create([
                'id_nasabah' => $nasabah->id,
                'keterangan' => 'Pengajuan perubahan PIN oleh nasabah.',
                'catatan' => 'Bawa buku tabungan fisik ke loket untuk verifikasi.',
                'pin_baru_hash' => Hash::make($pinBaru),
                'status' => PermintaanResetPin::STATUS_PENDING,
                'diajukan_oleh_role' => 'nasabah',
                'nomor_tiket' => static::nomorTiketBaru(),
                'diajukan_pada' => now('Asia/Jakarta'),
            ]);

            ActivityLog::catat(
                $nasabah->user_id,
                'ajuan_ubah_pin',
                'Nasabah mengajukan perubahan PIN untuk rekening '.$nasabah->no_rekening.'.'
            );

            foreach (User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR])->where('status', 'active')->get() as $petugas) {
                UserNotification::kirim(
                    $petugas,
                    UserNotification::TIPE_INFO,
                    'Pengajuan Perubahan PIN',
                    'Nasabah '.$nasabah->nama_siswa.' ('.$nasabah->no_rekening.') mengajukan perubahan PIN dan menunggu persetujuan.',
                    null,
                );
            }

            return $permintaan;
        });
    }

    /**
     * Setujui pengajuan. Admin maupun Supervisor sama-sama berwenang;
     * cukup salah satu, tidak perlu keduanya.
     *
     * Row lock + pengecekan status mencegah persetujuan ganda saat dua petugas
     * membuka pengajuan yang sama secara bersamaan.
     */
    public static function setujui(PermintaanResetPin $permintaan, User $aktor, ?string $catatan = null): PermintaanResetPin
    {
        if (! in_array($aktor->role, [User::ROLE_ADMIN, User::ROLE_SUPERVISOR], true)) {
            throw new \RuntimeException('Anda tidak berwenang menyetujui pengajuan PIN.');
        }

        return DB::transaction(function () use ($permintaan, $aktor, $catatan) {
            $terkunci = PermintaanResetPin::whereKey($permintaan->getKey())
                ->lockForUpdate()
                ->first();

            if (! $terkunci) {
                throw new \RuntimeException('Pengajuan tidak ditemukan.');
            }

            if ($terkunci->status !== PermintaanResetPin::STATUS_PENDING) {
                throw new \RuntimeException('Pengajuan ini sudah '.$terkunci->status_label.' dan tidak dapat diproses lagi.');
            }

            if (! $terkunci->pin_baru_hash) {
                throw new \RuntimeException('Pengajuan ini tidak memuat PIN baru dari nasabah.');
            }

            $nasabah = $terkunci->nasabah;

            // Terapkan PIN baru secara otomatis dari hash yang tersimpan.
            $nasabah->forceFill(['pin_keamanan' => $terkunci->pin_baru_hash])->save();

            $akun = $nasabah->user ?? $nasabah->ensureUser();
            $akun->update(['password' => $terkunci->pin_baru_hash]);

            $terkunci->update([
                'status' => PermintaanResetPin::STATUS_DIPROSES,
                'diproses_oleh' => $aktor->id,
                'diproses_at' => now(),
                'disetujui_at' => now(),
                'teller_id' => $aktor->role === User::ROLE_TELLER ? $aktor->id : $terkunci->teller_id,
                'supervisor_id' => $aktor->role === User::ROLE_SUPERVISOR ? $aktor->id : $terkunci->supervisor_id,
                'keterangan' => $catatan ?: $terkunci->keterangan,
            ]);

            ActivityLog::catat(
                $aktor->id,
                'setujui_ubah_pin',
                'Pengajuan perubahan PIN nasabah '.$nasabah->nama_siswa.' ('.$nasabah->no_rekening.') disetujui oleh '.$aktor->nama_petugas.'. PIN baru langsung diterapkan.'
            );

            UserNotification::kirim(
                $nasabah->user_id,
                UserNotification::TIPE_SUCCESS,
                'Pengajuan PIN Disetujui',
                'Pengajuan perubahan PIN Anda telah disetujui oleh petugas. PIN baru sudah berlaku, gunakan PIN baru untuk login dan transaksi.',
                null,
            );

            return $terkunci->fresh();
        });
    }

    /**
     * Tolak pengajuan. PIN lama tetap berlaku.
     */
    public static function tolak(PermintaanResetPin $permintaan, User $aktor, string $alasan): PermintaanResetPin
    {
        if (! in_array($aktor->role, [User::ROLE_ADMIN, User::ROLE_SUPERVISOR], true)) {
            throw new \RuntimeException('Anda tidak berwenang menolak pengajuan PIN.');
        }

        if (trim($alasan) === '') {
            throw new \InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($permintaan, $aktor, $alasan) {
            $terkunci = PermintaanResetPin::whereKey($permintaan->getKey())
                ->lockForUpdate()
                ->first();

            if (! $terkunci) {
                throw new \RuntimeException('Pengajuan tidak ditemukan.');
            }

            if ($terkunci->status !== PermintaanResetPin::STATUS_PENDING) {
                throw new \RuntimeException('Pengajuan ini sudah '.$terkunci->status_label.' dan tidak dapat diproses lagi.');
            }

            $nasabah = $terkunci->nasabah;

            $terkunci->update([
                'status' => PermintaanResetPin::STATUS_DITOLAK,
                'diproses_oleh' => $aktor->id,
                'diproses_at' => now(),
                'ditolak_at' => now(),
                'alasan_penolakan' => $alasan,
            ]);

            // Hash PIN baru dihapus agar tidak ada jejak hash yang tidak terpakai.
            $terkunci->forceFill(['pin_baru_hash' => null])->save();

            ActivityLog::catat(
                $aktor->id,
                'tolak_ubah_pin',
                'Pengajuan perubahan PIN nasabah '.$nasabah->nama_siswa.' ('.$nasabah->no_rekening.') ditolak oleh '.$aktor->nama_petugas.'. PIN lama tetap digunakan.'
            );

            UserNotification::kirim(
                $nasabah->user_id,
                UserNotification::TIPE_DANGER,
                'Pengajuan PIN Ditolak',
                'Pengajuan perubahan PIN Anda ditolak. Alasan: '.$alasan.' PIN lama Anda tetap berlaku.',
                null,
            );

            return $terkunci->fresh();
        });
    }

    /**
     * Nasabah membatalkan pengajuan miliknya yang masih menunggu.
     * Kepemilikan wajib dicek di sini agar ID dari request tidak dipercaya.
     */
    public static function batalkan(PermintaanResetPin $permintaan, Nasabah $nasabah): PermintaanResetPin
    {
        return DB::transaction(function () use ($permintaan, $nasabah) {
            $terkunci = PermintaanResetPin::whereKey($permintaan->getKey())
                ->lockForUpdate()
                ->first();

            if (! $terkunci) {
                throw new \RuntimeException('Pengajuan tidak ditemukan.');
            }

            if ((int) $terkunci->id_nasabah !== (int) $nasabah->id) {
                throw new \RuntimeException('Pengajuan ini bukan milik Anda.');
            }

            if ($terkunci->status !== PermintaanResetPin::STATUS_PENDING) {
                throw new \RuntimeException('Pengajuan ini sudah '.$terkunci->status_label.' dan tidak dapat dibatalkan.');
            }

            $terkunci->update([
                'status' => PermintaanResetPin::STATUS_DIBATALKAN,
                'dibatalkan_pada' => now(),
                'catatan' => 'Dibatalkan oleh nasabah.',
            ]);

            // Hash PIN baru dihapus karena pengajuan tidak dilanjutkan.
            $terkunci->forceFill(['pin_baru_hash' => null])->save();

            ActivityLog::catat(
                $nasabah->user_id,
                'batalkan_ubah_pin',
                'Nasabah membatalkan pengajuan perubahan PIN untuk rekening '.$nasabah->no_rekening.'.'
            );

            return $terkunci->fresh();
        });
    }

    /**
     * Pola PIN lemah: angka sama semua, berurutan, atau berulang.
     */
    public static function pinLemah(string $pin): bool
    {
        if (! preg_match('/^\d{6}$/', $pin)) {
            return true;
        }

        $digit = array_map('intval', str_split($pin));

        if (count(array_unique($digit)) === 1) {
            return true;
        }

        $naik = true;
        $turun = true;

        for ($i = 1; $i < 6; $i++) {
            if ($digit[$i] !== ($digit[$i - 1] + 1) % 10) {
                $naik = false;
            }
            if ($digit[$i] !== ($digit[$i - 1] + 9) % 10) {
                $turun = false;
            }
        }

        if ($naik || $turun) {
            return true;
        }

        // Pola berulang: 121212, 123123, 112233, dan sejenisnya.
        foreach ([2, 3] as $panjang) {
            $pola = substr($pin, 0, $panjang);
            if (str_repeat($pola, (int) (6 / $panjang)) === $pin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nomor tiket unik: REQ-PIN-YYYYMMDD-NN.
     */
    public static function nomorTiketBaru(): string
    {
        $tanggal = now('Asia/Jakarta')->format('Ymd');
        $awalan = 'REQ-PIN-'.$tanggal.'-';

        $urutan = PermintaanResetPin::where('nomor_tiket', 'like', $awalan.'%')->count() + 1;

        do {
            $tiket = $awalan.str_pad((string) $urutan, 2, '0', STR_PAD_LEFT);
            $urutan++;
        } while (PermintaanResetPin::where('nomor_tiket', $tiket)->exists());

        return $tiket;
    }
}
