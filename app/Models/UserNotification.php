<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $tipe
 * @property string $judul
 * @property string|null $pesan
 * @property string|null $url
 * @property string|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $sudah_dibaca
 * @property-read string $icon
 * @property-read User $user
 */
#[Fillable(['user_id', 'tipe', 'judul', 'pesan', 'url', 'read_at'])]
class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    public const TIPE_INFO = 'info';

    public const TIPE_SUCCESS = 'success';

    public const TIPE_WARNING = 'warning';

    public const TIPE_DANGER = 'danger';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeSudahDibaca(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeUntukUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Buat notifikasi baru untuk seorang user.
     */
    public static function kirim(User|int $user, string $tipe, string $judul, ?string $pesan = null, ?string $url = null): self
    {
        return self::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'tipe' => $tipe,
            'judul' => $judul,
            'pesan' => $pesan,
            'url' => $url,
        ]);
    }

    public function tandaiDibaca(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function getSudahDibacaAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function getIconAttribute(): string
    {
        return match ($this->tipe) {
            self::TIPE_SUCCESS => 'fa-circle-check',
            self::TIPE_WARNING => 'fa-triangle-exclamation',
            self::TIPE_DANGER => 'fa-circle-xmark',
            default => 'fa-circle-info',
        };
    }
}
