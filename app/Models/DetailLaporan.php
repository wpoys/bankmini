<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $laporan_id
 * @property int $transaksi_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read LaporanHarian $laporan
 * @property-read Transaksi $transaksi
 */
#[Fillable(['laporan_id', 'transaksi_id'])]
class DetailLaporan extends Model
{
    protected $table = 'detail_laporan';

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanHarian::class, 'laporan_id');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }
}
