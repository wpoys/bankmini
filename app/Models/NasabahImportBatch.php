<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['imported_by', 'file_name', 'total_rows', 'valid_rows', 'invalid_rows', 'imported_rows', 'failed_rows', 'status', 'completed_at'])]
class NasabahImportBatch extends Model
{
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(NasabahImportBatchRow::class, 'batch_id');
    }
}
