<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $no_rekening
 * @property string|null $kode_jurusan
 * @property string|null $qr_code
 * @property string $nis
 * @property string $nama_siswa
 * @property string $kelas
 * @property string $produk
 * @property string|null $no_hp Legacy database column; not used by the application.
 * @property string $pin_keamanan
 * @property int $saldo
 * @property bool $arsip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $status
 * @property-read string $produk_label
 * @property-read string $saldo_rupiah
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Transaksi> $transaksi
 */
#[Fillable(['user_id', 'no_rekening', 'kode_jurusan', 'qr_code', 'nis', 'nama_siswa', 'kelas', 'produk', 'pin_keamanan', 'saldo', 'arsip'])]
// pin_keamanan di-hidden: hash PIN tidak boleh bocor ke response API/JSON.
#[Hidden(['pin_keamanan'])]
class Nasabah extends Model
{
    use HasFactory;

    protected $table = 'nasabah';

    public const PRODUK_SISWA = 'siswa';

    public const PRODUK_GURU = 'guru';

    public const KELAS = [
        'RPL 1',
        'RPL 2',
        'AKL 1',
        'AKL 2',
        'AKL 3',
        'AKL 4',
        'LPS 1',
        'LPS 2',
        'MP 1',
        'MP 2',
        'BR 1',
        'BR 2',
        'DKV 1',
        'DKV 2',
        'TKJ 1',
        'TKJ 2',
    ];

    public const KELAS_GROUPS = [
        'Rekayasa Perangkat Lunak (RPL)' => ['RPL 1', 'RPL 2'],
        'Akuntansi dan Keuangan Lembaga (AKL)' => ['AKL 1', 'AKL 2', 'AKL 3', 'AKL 4'],
        'Layanan Perbankan Syariah (LPS)' => ['LPS 1', 'LPS 2'],
        'Manajemen Perkantoran dan Layanan Bisnis (MP)' => ['MP 1', 'MP 2'],
        'Bisnis Ritel / Pemasaran (BR)' => ['BR 1', 'BR 2'],
        'Desain Komunikasi Visual (DKV)' => ['DKV 1', 'DKV 2'],
        'Teknik Komputer dan Jaringan (TKJ)' => ['TKJ 1', 'TKJ 2'],
    ];

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_ARSIP = 'arsip';

    protected function casts(): array
    {
        return [
            'saldo' => 'integer',
            'pin_keamanan' => 'hashed',
            'arsip' => 'boolean',
        ];
    }

    public function isArsip(): bool
    {
        return (bool) $this->arsip;
    }

    /**
     * Status nasabah: aktif / arsip (turunan dari flag arsip).
     */
    public function getStatusAttribute(): string
    {
        return $this->isArsip() ? self::STATUS_ARSIP : self::STATUS_AKTIF;
    }

    /**
     * Pastikan setiap nasabah memiliki akun user terkait.
     * Jika user belum ada, buat otomatis (username = NIS).
     */
    public function ensureUser(): User
    {
        if ($this->user_id && $this->user) {
            return $this->user;
        }

        $user = User::create([
            'username' => $this->nis,
            'email' => null,
            'password' => User::DEFAULT_PASSWORD,
            'nama_petugas' => $this->nama_siswa,
            'role' => User::ROLE_NASABAH,
            'status' => 'active',
        ]);

        $this->user_id = $user->id;
        $this->save();

        return $user;
    }

    public function getProdukLabelAttribute(): string
    {
        return $this->produk === self::PRODUK_GURU ? 'Tabungan Guru/Karyawan' : 'Tabungan Siswa';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_nasabah');
    }

    public function pengajuanPin(): HasMany
    {
        return $this->hasMany(PermintaanResetPin::class, 'id_nasabah');
    }

    public function getSaldoRupiahAttribute(): string
    {
        return 'Rp ' . number_format($this->saldo, 0, ',', '.');
    }

    /**
     * Inisial nama untuk avatar kartu identitas (maks. 2 huruf).
     */
    public function getInisialAttribute(): string
    {
        $kata = preg_split('/\s+/', trim((string) $this->nama_siswa)) ?: [];
        $inisial = '';

        foreach (array_slice($kata, 0, 2) as $k) {
            $inisial .= mb_strtoupper(mb_substr($k, 0, 1));
        }

        return $inisial !== '' ? $inisial : 'N';
    }

    /**
     * Nama jurusan lengkap berdasarkan kelas, fallback kode jurusan.
     */
    public function getJurusanAttribute(): string
    {
        foreach (self::KELAS_GROUPS as $nama => $daftar) {
            if (in_array($this->kelas, $daftar, true)) {
                return $nama;
            }
        }

        return (string) ($this->kode_jurusan ?: '-');
    }

    /**
     * Label kelas + jurusan, contoh: XI RPL 1 • Rekayasa Perangkat Lunak (RPL)
     */
    public function getKelasJurusanAttribute(): string
    {
        return trim($this->kelas.' • '.$this->jurusan, ' •');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isArsip() ? 'ARSIP' : 'AKTIF (TERVERIFIKASI)';
    }

    public function getStatusCifAttribute(): string
    {
        return $this->isArsip() ? 'Nonaktif (Arsip)' : 'Aktif / Terverifikasi';
    }

    public function getTanggalPembukaanAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->created_at;
    }

    public function getUsernameAttribute(): string
    {
        return (string) ($this->user?->username ?? $this->nis);
    }
}
