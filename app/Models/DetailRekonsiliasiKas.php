<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rekonsiliasi_id
 * @property int $pecahan
 * @property int $jumlah_lembar
 * @property int $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read RekonsiliasiKas $rekonsiliasi
 */
#[Fillable(['rekonsiliasi_id', 'pecahan', 'jumlah_lembar', 'subtotal'])]
class DetailRekonsiliasiKas extends Model
{
    protected $table = 'detail_rekonsiliasi_kas';

    public function rekonsiliasi(): BelongsTo
    {
        return $this->belongsTo(RekonsiliasiKas::class, 'rekonsiliasi_id');
    }
}
