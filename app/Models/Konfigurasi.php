<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * @property int $id
 * @property string $key_name
 * @property string $value
 * @property string|null $deskripsi
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
#[Fillable(['key_name', 'value', 'deskripsi', 'updated_by'])]
class Konfigurasi extends Model
{
    use HasFactory;

    protected $table = 'konfigurasi';

    public const SALDO_MINIMUM = 'saldo_minimum';

    public const BATAS_PENARIKAN_BESAR = 'batas_penarikan_besar';

    public const KAS_AWAL_HARIAN = 'kas_awal_harian';

    public const KODE_JURUSAN = 'kode_jurusan';

    public const NAMA_SEKOLAH = 'nama_sekolah';

    public const ALAMAT_SEKOLAH = 'alamat_sekolah';

    public const KOTA_SEKOLAH = 'kota_sekolah';

    public const JAM_OPERASIONAL = 'jam_operasional';

    public const LOKASI_LOKET = 'lokasi_loket';

    /**
     * Kode sandi persetujuan koreksi transaksi — disimpan sebagai hash
     * (tidak pernah dalam bentuk plaintext).
     */
    public const KODE_SANDI_KOREKSI = 'kode_sandi_koreksi';

    /**
     * Kode jurusan yang dapat dipakai pada format rekening
     * (dipakai bila tabel konfigurasi belum diset).
     */
    public const KODE_JURUSAN_DEFAULT = [
        'RPL',
        'DKV',
        'AK',
        'TKJ',   
        'MM',
        'BDP',
        'OTKP',
        'AKL',
        'TB',
        'TBSM',
        'TO',
        'TKR',
        'TI',
        'PH',
        'AS',
        'TFLM',
        'AP',
        'UPW',
        'BRD',
        'HTL',
        'GURU',
    ];

    public const DESKRIPSI = [
        self::SALDO_MINIMUM => 'Saldo mengendap minimum (batas saldo yang harus tersisa)',
        self::BATAS_PENARIKAN_BESAR => 'Nominal penarikan di atas nilai ini memerlukan konfirmasi (H-1)',
        self::KAS_AWAL_HARIAN => 'Perkiraan kas laci yang disiapkan per piket',
        self::KODE_JURUSAN => 'Daftar kode jurusan untuk format rekening (pisahkan dengan koma)',
        self::NAMA_SEKOLAH => 'Nama sekolah yang tampil pada portal nasabah dan struk',
        self::ALAMAT_SEKOLAH => 'Alamat sekolah untuk portal dan slip transaksi',
        self::KOTA_SEKOLAH => 'Kota sekolah untuk identitas portal nasabah',
        self::JAM_OPERASIONAL => 'Jam operasional loket kasir sekolah',
        self::LOKASI_LOKET => 'Lokasi loket kasir sekolah',
    ];

    /**
     * Nilai konfigurasi sebagai string mentah (dengan default bila belum diset).
     */
    public static function nilai(string $key, mixed $default = null): mixed
    {
        $record = static::query()->where('key_name', $key)->first();

        return $record ? $record->value : $default;
    }

    public static function saldoMinimum(): int
    {
        return (int) static::nilai(self::SALDO_MINIMUM, 10000);
    }

    public static function batasPenarikanBesar(): int
    {
        return (int) static::nilai(self::BATAS_PENARIKAN_BESAR, 1000000);
    }

    public static function kasAwalHarian(): int
    {
        return (int) static::nilai(self::KAS_AWAL_HARIAN, 500000);
    }

    /**
     * Daftar kode jurusan yang sah untuk format rekening (dari konfigurasi).
     */
    public static function kodeJurusan(): array
    {
        $value = (string) static::nilai(self::KODE_JURUSAN);

        if (trim($value) === '') {
            return self::KODE_JURUSAN_DEFAULT;
        }

        $parts = preg_split('/[,\s]+/', $value) ?: [];

        $list = array_values(array_filter(array_map(
            fn($kode) => strtoupper(trim((string) $kode)),
            $parts
        ), fn($kode) => $kode !== ''));

        return $list ?: self::KODE_JURUSAN_DEFAULT;
    }

    public static function validKodeJurusan(?string $kode): bool
    {
        return in_array(strtoupper(trim((string) $kode)), static::kodeJurusan(), true);
    }

    /**
     * Apakah kode sandi persetujuan koreksi sudah disetel admin?
     */
    public static function kodeSandiKoreksiTersetel(): bool
    {
        return trim((string) static::nilai(self::KODE_SANDI_KOREKSI, '')) !== '';
    }

    /**
     * Cocokkan input dengan hash kode sandi koreksi.
     * Mengembalikan true bila kode sandi belum disetel (fitur nonaktif).
     */
    public static function validKodeSandiKoreksi(?string $input): bool
    {
        $hash = trim((string) static::nilai(self::KODE_SANDI_KOREKSI, ''));

        if ($hash === '') {
            return true;
        }

        return $input !== null && $input !== '' && Hash::check($input, $hash);
    }

    /**
     * Simpan satu nilai konfigurasi (upsert).
     */
    public static function set(string $key, mixed $value, ?string $deskripsi = null, ?int $updatedBy = null): self
    {
        $record = static::query()->firstOrNew(['key_name' => $key]);

        $record->value = (string) $value;
        $record->deskripsi = $deskripsi ?? $record->deskripsi;

        if ($updatedBy) {
            $record->updated_by = $updatedBy;
        }

        $record->save();

        return $record;
    }
}
