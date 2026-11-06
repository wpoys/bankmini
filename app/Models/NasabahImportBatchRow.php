<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['batch_id', 'row_number', 'status', 'errors'])]
#[Hidden(['errors'])]
class NasabahImportBatchRow extends Model
{
    protected function casts(): array
    {
        return ['errors' => 'array'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NasabahImportBatch::class, 'batch_id');
    }
}
