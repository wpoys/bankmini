<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\JurnalAkuntansi;
use App\Models\Konfigurasi;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Inti alur pendaftaran nasabah (Admin & Customer Service) dan sinkronisasi
 * saat mengubah data nasabah.
 *
 * Prinsip:
 * - Satu akun User dibuat untuk tiap nasabah (username = NIS, role = nasabah).
 * - Email nasabah disimpan pada users.email (nullable, unik bila diisi).
 * - PIN disimpan ter-hash pada nasabah.pin_keamanan dan sebagai password user.
 * - Nomor rekening & QR dibuat otomatis; saldo awal dicatat sebagai setoran
 *   awal beserta jurnal akuntansinya (bila > 0).
 * - Seluruh alur dibungkus satu DB::transaction agar atomik.
 */
class PendaftaranNasabah
{
    /**
     * Aturan validasi pendaftaran/sinkronisasi nasabah.
     *
     * @param  bool  $untukUpdate  true pada form edit (PIN opsional, saldo tidak diedit langsung).
     */
    public static function aturan(bool $untukUpdate = false, ?Nasabah $nasabah = null): array
    {
        $nis = Rule::unique('nasabah', 'nis');
        $username = Rule::unique('users', 'username');
        $email = Rule::unique('users', 'email');

        if ($untukUpdate && $nasabah) {
            $nis->ignore($nasabah->id);
            $username->ignore($nasabah->user_id);
            $email->ignore($nasabah->user_id);
        }

        $rules = [
            'nama_siswa' => ['required', 'string', 'min:3', 'max:150'],
            'nis' => ['required', 'string', 'max:30', $nis, $username],
            'kelas' => ['required', Rule::in(Nasabah::KELAS)],
            'email' => [($untukUpdate ? 'nullable' : 'required'), 'email', 'max:191', $email],
            'pin_keamanan' => ['nullable', 'digits:6', 'confirmed'],
        ];

        if (! $untukUpdate) {
            $rules['saldo_awal'] = ['required', 'integer', 'min:0', 'max:999999999999'];
        }

        return $rules;
    }

    /**
     * Sekali jalan: buat akun user lalu data nasabah lengkap (rekening, QR, PIN,
     * email, saldo awal + transaksi setoran awal + jurnal, activity log,
     * notifikasi) di dalam satu transaksi database.
     */
    public static function buat(?int $petugasId, array $data): Nasabah
    {
        return DB::transaction(function () use ($petugasId, $data) {
            $email = self::emailAtauNull($data['email'] ?? null);
            $saldoAwal = (int) ($data['saldo_awal'] ?? 0);

            $user = User::create([
                'username' => $data['nis'],
                'email' => $email,
                'password' => Hash::make(bin2hex(random_bytes(32))),
                'nama_petugas' => $data['nama_siswa'],
                'role' => User::ROLE_NASABAH,
                'status' => 'active',
                'status_aktivasi' => User::AKTIVASI_BELUM_AKTIF,
            ]);

            $kodeJurusan = KodeJurusan::dariKelas($data['kelas']);

            if (! Konfigurasi::validKodeJurusan($kodeJurusan)) {
                throw new \InvalidArgumentException(
                    "Kode jurusan '{$kodeJurusan}' tidak terdaftar pada konfigurasi. Hubungi Administrator."
                );
            }

            $noRekening = NomorGenerator::rekening($kodeJurusan);

            $nasabah = Nasabah::create([
                'user_id' => $user->id,
                'no_rekening' => $noRekening,
                'kode_jurusan' => $kodeJurusan,
                'qr_code' => $noRekening,
                'nis' => $data['nis'],
                'nama_siswa' => $data['nama_siswa'],
                'kelas' => $data['kelas'],
                'produk' => Nasabah::PRODUK_SISWA,
                'pin_keamanan' => null,
                'saldo' => 0,
                'arsip' => false,
            ]);

            if ($saldoAwal > 0) {
                self::catatSetoranAwal($nasabah, $petugasId, $saldoAwal);
            }

            ActivityLog::catat(
                $petugasId,
                'tambah_nasabah',
                "Nasabah {$nasabah->nama_siswa} dibuat dengan rekening {$nasabah->no_rekening}."
            );

            UserNotification::kirim(
                $user,
                UserNotification::TIPE_SUCCESS,
                'Akun Tabungan Dibuat',
                "Selamat {$nasabah->nama_siswa}, rekening tabungan Anda berhasil dibuat dengan nomor {$nasabah->no_rekening}. Username login menggunakan NIS Anda: {$user->username}.",
                null,
            );

            return $nasabah;
        });
    }

    /**
     * Sinkronkan perubahan nasabah ke akun user di dalam satu transaksi:
     * nama -> nama_petugas, NIS -> username, email -> users.email,
     * PIN baru -> pin_keamanan + password user. Saldo tidak diedit langsung.
     */
    public static function perbarui(Nasabah $nasabah, ?int $petugasId, array $data): Nasabah
    {
        return DB::transaction(function () use ($nasabah, $petugasId, $data) {
            $email = self::emailAtauNull($data['email'] ?? null);
            $akun = $nasabah->user ?? $nasabah->ensureUser();

            $nasabah->fill([
                'nama_siswa' => $data['nama_siswa'],
                'nis' => $data['nis'],
                'kelas' => $data['kelas'],
                'kode_jurusan' => KodeJurusan::dariKelas($data['kelas']),
            ]);

            if (! empty($data['pin_keamanan'])) {
                $nasabah->pin_keamanan = Hash::make($data['pin_keamanan']);
            }

            $nasabah->save();

            $akun->update([
                'username' => $data['nis'],
                'nama_petugas' => $data['nama_siswa'],
                'email' => $email,
            ]);

            if (! empty($data['pin_keamanan'])) {
                $akun->update(['password' => Hash::make($data['pin_keamanan'])]);
            }

            ActivityLog::catat(
                $petugasId,
                'ubah_nasabah',
                "Nasabah {$nasabah->nama_siswa} diperbarui."
            );

            return $nasabah;
        });
    }

    /**
     * Catat setoran awal sebagai transaksi resmi + jurnal akuntansi agar
     * saldo selalu konsisten dengan histori transaksi.
     */
    protected static function catatSetoranAwal(Nasabah $nasabah, ?int $petugasId, int $nominal): void
    {
        $transaksi = Transaksi::create([
            'id_nasabah' => $nasabah->id,
            'id_user' => $petugasId,
            'tanggal' => today(),
            'jenis_trans' => Transaksi::JENIS_SETORAN,
            'nominal' => $nominal,
            'no_struk' => NomorGenerator::struk(),
            'status' => Transaksi::STATUS_APPROVED,
        ]);

        $nasabah->increment('saldo', $nominal);

        self::catatJurnal((int) $transaksi->id, $nominal, Transaksi::JENIS_SETORAN);
    }

    /**
     * Tulis pasangan debit/kredit jurnal untuk setoran atau penarikan,
     * konsisten dengan aturan transaksi lain di aplikasi.
     */
    public static function catatJurnal(int $idTransaksi, int $nominal, string $jenis): void
    {
        $entries = $jenis === Transaksi::JENIS_SETORAN
            ? [
                ['kode_akun' => KodeAkun::KAS, 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => KodeAkun::TABUNGAN, 'posisi' => 'kredit', 'jumlah' => $nominal],
            ]
            : [
                ['kode_akun' => KodeAkun::TABUNGAN, 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => KodeAkun::KAS, 'posisi' => 'kredit', 'jumlah' => $nominal],
            ];

        foreach ($entries as $entry) {
            JurnalAkuntansi::create($entry + ['id_transaksi' => $idTransaksi]);
        }
    }

    protected static function emailAtauNull(mixed $email): ?string
    {
        $email = is_string($email) ? trim($email) : '';

        return $email === '' ? null : $email;
    }
}
