<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Verifikasi transaksi supervisor per hari.
 *
 * Satu baris per tanggal: transaksi di kelompokkan berdasarkan tanggal,
 * diverifikasi sekali, dan tidak dapat diverifikasi ulang.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property int $jumlah_transaksi
 * @property int $total_setoran
 * @property int $total_penarikan
 * @property int $total_kas
 * @property int|null $selisih
 * @property int $transaksi_tercatat
 * @property int|null $verifikasi_oleh
 * @property \Illuminate\Support\Carbon|null $verifikasi_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $verifikator
 * @property-read string $total_kas_rupiah
 * @property-read string $selisih_rupiah
 * @property-read bool $sudahDiverifikasi
 * @property-read bool $adaTransaksiBaru
 */
#[Fillable([
    'tanggal',
    'jumlah_transaksi',
    'total_setoran',
    'total_penarikan',
    'total_kas',
    'selisih',
    'transaksi_tercatat',
    'verifikasi_oleh',
    'verifikasi_at',
])]
class VerifikasiHarian extends Model
{
    use HasFactory;

    protected $table = 'verifikasi_harian';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah_transaksi' => 'integer',
            'total_setoran' => 'integer',
            'total_penarikan' => 'integer',
            'total_kas' => 'integer',
            'selisih' => 'integer',
            'transaksi_tercatat' => 'integer',
            'verifikasi_at' => 'datetime',
        ];
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifikasi_oleh');
    }

    public function scopeSudahDiverifikasi(Builder $query): Builder
    {
        return $query->whereNotNull('verifikasi_at');
    }

    public function getSudahDiverifikasiAttribute(): bool
    {
        return $this->verifikasi_at !== null;
    }

    public function getTotalKasRupiahAttribute(): string
    {
        return 'Rp '.number_format((int) $this->total_kas, 0, ',', '.');
    }

    public function getSelisihRupiahAttribute(): string
    {
        return $this->selisih === null
            ? '-'
            : 'Rp '.number_format((int) $this->selisih, 0, ',', '.');
    }

    /**
     * Transaksi baru muncul setelah tanggal tersebut diverifikasi.
     */
    public function getAdaTransaksiBaruAttribute(): bool
    {
        return $this->sudah_diverifikasi
            && (int) Transaksi::whereDate('tanggal', $this->tanggal)
                ->where('status', '!=', Transaksi::STATUS_DITOLAK)
                ->count() > (int) $this->transaksi_tercatat;
    }
}
