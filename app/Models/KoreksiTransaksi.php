<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $transaksi_id
 * @property string|null $jenis
 * @property int $id_nasabah
 * @property int $id_user
 * @property int|null $nominal
 * @property string $alasan
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $catatan_approver
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $nominal_efektif
 * @property-read string|null $jenis_label
 * @property-read bool $tanpa_transaksi_asli
 * @property-read string $status_label
 * @property-read Transaksi|null $transaksi
 * @property-read Nasabah $nasabah
 * @property-read User $pengaju
 * @property-read User|null $approver
 */
#[Fillable([
    'transaksi_id',
    'jenis',
    'id_nasabah',
    'id_user',
    'nominal',
    'alasan',
    'status',
    'approved_by',
    'approved_at',
    'catatan_approver',
])]
class KoreksiTransaksi extends Model
{
    use HasFactory;

    protected $table = 'koreksi_transaksi';

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'nominal' => 'integer',
        ];
    }

    /**
     * Nominal yang berlaku untuk koreksi (mengutamakan nilai yang
     * diisi saat pengajuan, fallback ke nominal transaksi asli).
     */
    public function getNominalEfektifAttribute(): int
    {
        return $this->nominal ?? (int) ($this->transaksi->nominal ?? 0);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'id_nasabah');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    /**
     * Klasifikasi arah koreksi. Nilai mengikuti konstanta
     * Transaksi::JENIS_KOREKSI_* ('koreksi_setoran' / 'koreksi_penarikan').
     */
    public function getJenisLabelAttribute(): ?string
    {
        return match ($this->jenis) {
            Transaksi::JENIS_KOREKSI_SETORAN => 'Setoran Koreksi (uang masuk)',
            Transaksi::JENIS_KOREKSI_PENARIKAN => 'Penarikan Koreksi (uang keluar)',
            default => null,
        };
    }

    /**
     * True bila koreksi diajukan tanpa transaksi asli
     * (penemuan uang rusak/palsu/selisih).
     */
    public function getTanpaTransaksiAsliAttribute(): bool
    {
        return $this->transaksi_id === null;
    }
}
