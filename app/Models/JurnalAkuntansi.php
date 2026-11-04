<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $id_transaksi
 * @property string $kode_akun
 * @property string $posisi
 * @property int $jumlah
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Transaksi|null $transaksi
 */
#[Fillable(['id_transaksi', 'kode_akun', 'posisi', 'jumlah'])]
class JurnalAkuntansi extends Model
{
    protected $table = 'jurnal_akuntansi';

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }
}
