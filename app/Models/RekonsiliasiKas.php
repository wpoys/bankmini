<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $laporan_id
 * @property int $teller_id
 * @property int $total_fisik
 * @property int|null $selisih
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read LaporanHarian $laporan
 * @property-read User $teller
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DetailRekonsiliasiKas> $detail
 */
#[Fillable(['laporan_id', 'teller_id', 'total_fisik', 'selisih', 'status'])]
class RekonsiliasiKas extends Model
{
    protected $table = 'rekonsiliasi_kas';

    protected function casts(): array
    {
        return [
            'total_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanHarian::class, 'laporan_id');
    }

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailRekonsiliasiKas::class, 'rekonsiliasi_id');
    }
}
