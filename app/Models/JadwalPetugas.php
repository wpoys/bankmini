<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon $tanggal
 * @property string|null $hari
 * @property \Illuminate\Support\Carbon $jam_mulai
 * @property \Illuminate\Support\Carbon $jam_selesai
 * @property string $status
 * @property string|null $keterangan
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $nama_petugas
 * @property-read string $bagian
 * @property-read string $status_label
 * @property-read string $jam_label
 * @property-read User|null $user
 * @property-read User|null $pembuat
 */
#[Fillable(['user_id', 'tanggal', 'hari', 'jam_mulai', 'jam_selesai', 'status', 'keterangan', 'created_by'])]
class JadwalPetugas extends Model
{
    use HasFactory;

    protected $table = 'jadwal_petugas';

    public const STATUS_DIJADWALKAN = 'dijadwalkan';

    public const STATUS_BERTUGAS = 'bertugas';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNamaPetugasAttribute(): string
    {
        return $this->user?->nama_petugas ?? '-';
    }

    public function getBagianAttribute(): string
    {
        return $this->user?->role_label ?? '-';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_BERTUGAS => 'Bertugas',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => 'Dijadwalkan',
        };
    }

    public function getJamLabelAttribute(): string
    {
        $mulai = $this->jam_mulai instanceof \Carbon\CarbonInterface
            ? $this->jam_mulai->format('H:i')
            : substr((string) $this->jam_mulai, 0, 5);
        $selesai = $this->jam_selesai instanceof \Carbon\CarbonInterface
            ? $this->jam_selesai->format('H:i')
            : substr((string) $this->jam_selesai, 0, 5);

        return $mulai . ' - ' . $selesai;
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_mulai' => 'datetime:H:i',
            'jam_selesai' => 'datetime:H:i',
        ];
    }
}