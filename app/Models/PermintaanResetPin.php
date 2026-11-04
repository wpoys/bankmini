<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajuan perubahan/reset PIN nasabah.
 *
 * Keamanan: pin_baru_hash di-hidden agar tidak pernah bocor ke response API,
 * log, atau serialisasi. Nilai yang tersimpan adalah hash, bukan plaintext.
 *
 * @property int $id
 * @property int $id_nasabah
 * @property string|null $keterangan
 * @property string $status
 * @property string|null $pin_baru_hash
 * @property string|null $alasan_penolakan
 * @property string $diajukan_oleh_role
 * @property int|null $diproses_oleh
 * @property \Illuminate\Support\Carbon|null $diproses_at
 * @property \Illuminate\Support\Carbon|null $disetujui_at
 * @property \Illuminate\Support\Carbon|null $ditolak_at
 * @property string|null $nomor_tiket
 * @property int|null $teller_id
 * @property int|null $supervisor_id
 * @property \Illuminate\Support\Carbon|null $diajukan_pada
 * @property string|null $catatan
 * @property \Illuminate\Support\Carbon|null $dibatalkan_pada
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $status_label
 * @property-read string $status_badge
 * @property-read bool $bisa_diproses
 * @property-read bool $ada_pin_baru
 * @property-read Nasabah $nasabah
 * @property-read User|null $pemroses
 * @property-read User|null $teller
 * @property-read User|null $supervisor
 */
#[Fillable([
    'id_nasabah',
    'keterangan',
    'status',
    'pin_baru_hash',
    'alasan_penolakan',
    'diajukan_oleh_role',
    'diproses_oleh',
    'diproses_at',
    'disetujui_at',
    'ditolak_at',
    'nomor_tiket',
    'teller_id',
    'supervisor_id',
    'diajukan_pada',
    'catatan',
    'dibatalkan_pada',
])]
#[Hidden(['pin_baru_hash'])]
class PermintaanResetPin extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $table = 'permintaan_reset_pin';

    protected function casts(): array
    {
        return [
            'diproses_at' => 'datetime',
            'disetujui_at' => 'datetime',
            'ditolak_at' => 'datetime',
            'diajukan_pada' => 'datetime',
            'dibatalkan_pada' => 'datetime',
        ];
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'id_nasabah');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Hanya pengajuan menunggu yang masih dapat disetujui/ditolak.
     */
    public function getBisaDiprosesAttribute(): bool
    {
        return $this->isPending();
    }

    public function getBisaDibatalkanAttribute(): bool
    {
        return $this->isPending();
    }

    /**
     * Apakah pengajuan sudah memuat hash PIN baru dari nasabah.
     * Dipakai view untuk menentukan tombol, tanpa pernah mengirim hash-nya.
     */
    public function getAdaPinBaruAttribute(): bool
    {
        return $this->pin_baru_hash !== null && $this->pin_baru_hash !== '';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Teller',
            self::STATUS_DIPROSES => 'Disetujui',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'orange',
            self::STATUS_DIPROSES => 'green',
            self::STATUS_DITOLAK => 'red',
            self::STATUS_DIBATALKAN => 'gray',
            default => 'gray',
        };
    }

    /**
     * Nomor tiket siap tampil; data lama tanpa tiket memakai ID.
     */
    public function getNomorTiketTampilAttribute(): string
    {
        if ($this->nomor_tiket) {
            return '#'.$this->nomor_tiket;
        }

        return '#REQ-PIN-'.$this->created_at?->format('Ymd').'-'.str_pad((string) $this->id, 2, '0', STR_PAD_LEFT);
    }

    public function getDiajukanPadaTampilAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->diajukan_pada ?? $this->created_at;
    }

    public function getCatatanTampilAttribute(): string
    {
        return (string) ($this->catatan ?: $this->keterangan ?: '-');
    }
}
