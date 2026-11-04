<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $teller_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property int $saldo_awal_kas
 * @property int $total_setoran
 * @property int $total_penarikan
 * @property int $saldo_akhir_sistem
 * @property int|null $saldo_akhir_fisik
 * @property int|null $selisih
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $catatan_supervisor
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property int|null $kas_dipindahkan
 * @property int|null $kas_dipindahkan_oleh
 * @property \Illuminate\Support\Carbon|null $kas_dipindahkan_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $status_label
 * @property-read bool $kas_pindah_brankas
 * @property-read User $teller
 * @property-read User|null $approver
 * @property-read User|null $kasDipindahkanOleh
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DetailLaporan> $detailLaporan
 * @property-read RekonsiliasiKas|null $rekonsiliasi
 */
#[Fillable([
    'teller_id',
    'tanggal',
    'saldo_awal_kas',
    'total_setoran',
    'total_penarikan',
    'saldo_akhir_sistem',
    'saldo_akhir_fisik',
    'selisih',
    'status',
    'approved_by',
    'approved_at',
    'catatan_supervisor',
    'submitted_at',
    'kas_dipindahkan',
    'kas_dipindahkan_oleh',
    'kas_dipindahkan_at',
])]
class LaporanHarian extends Model
{
    use HasFactory;

    protected $table = 'laporan_harian';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'kas_dipindahkan_at' => 'datetime',
        ];
    }

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function detailLaporan(): HasMany
    {
        return $this->hasMany(DetailLaporan::class, 'laporan_id');
    }

    public function rekonsiliasi(): HasOne
    {
        return $this->hasOne(RekonsiliasiKas::class, 'laporan_id');
    }

    public function kasDipindahkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kas_dipindahkan_oleh');
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'pending') {
            return 'Ditahan (Pending)';
        }

        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getKasPindahBrankasAttribute(): bool
    {
        return $this->kas_dipindahkan !== null && $this->kas_dipindahkan > 0;
    }
}
