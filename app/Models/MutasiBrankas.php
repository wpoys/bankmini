<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiBrankas extends Model
{
    protected $table = 'mutasi_brankas';

    protected $guarded = ['id'];

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanHarian::class, 'laporan_id');
    }
}
