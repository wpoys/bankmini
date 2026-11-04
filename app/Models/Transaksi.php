<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * @property int $id
 * @property int $id_nasabah
 * @property int $id_user
 * @property \Illuminate\Support\Carbon $tanggal
 * @property string|null $jenis_trans
 * @property int $nominal
 * @property bool $konfirmasi_h1
 * @property string|null $catatan
 * @property string|null $no_struk
 * @property string $status
 * @property bool $dikoreksi
 * @property int|null $koreksi_id
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $nominal_rupiah
 * @property-read string $jenis_label
 * @property-read string $status_label
 * @property-read int $nominal_bertanda
 * @property-read int $saldo_sebelum
 * @property-read int $saldo_sesudah
 * @property-read string $tanggal_transaksi
 * @property-read string $jam_transaksi
 * @property-read string|null $struk_url
 * @property-read string|null $struk_cetak_url
 * @property-read string|null $struk_pdf_url
 * @property-read bool $boleh_lihat_struk
 * @property-read bool $boleh_cetak_struk
 * @property-read bool $boleh_unduh_struk
 * @property-read bool $is_setoran
 * @property-read bool $is_penarikan
 * @property-read Nasabah $nasabah
 * @property-read User $user
 * @property-read KoreksiTransaksi|null $koreksi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JurnalAkuntansi> $jurnal
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DetailLaporan> $detailLaporan
 */
#[Fillable(['id_nasabah', 'id_user', 'tanggal', 'jenis_trans', 'nominal', 'konfirmasi_h1', 'catatan', 'no_struk', 'status', 'dikoreksi', 'koreksi_id'])]
class Transaksi extends Model
{
    use HasFactory;

    public const JENIS_SETORAN = 'setoran';

    public const JENIS_PENARIKAN = 'penarikan';

    public const JENIS_KOREKSI_SETORAN = 'koreksi_setoran';

    public const JENIS_KOREKSI_PENARIKAN = 'koreksi_penarikan';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'transaksi';

    /**
     * Cache saldo berjalan hasil perhitungan ledger (tidak disimpan ke database).
     */
    protected ?int $saldoSebelumCache = null;

    protected ?int $saldoSesudahCache = null;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
            'konfirmasi_h1' => 'boolean',
        ];
    }

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'id_nasabah');
    }

    public function koreksi(): BelongsTo
    {
        return $this->belongsTo(KoreksiTransaksi::class, 'koreksi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function jurnal(): HasMany
    {
        return $this->hasMany(JurnalAkuntansi::class, 'id_transaksi');
    }

    public function detailLaporan(): HasMany
    {
        return $this->hasMany(DetailLaporan::class, 'transaksi_id');
    }

    public function getNominalRupiahAttribute(): string
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis_trans) {
            self::JENIS_SETORAN => 'Setoran',
            self::JENIS_PENARIKAN => 'Penarikan',
            self::JENIS_KOREKSI_SETORAN => 'Koreksi Setoran',
            self::JENIS_KOREKSI_PENARIKAN => 'Koreksi Penarikan',
            default => ucfirst((string) $this->jenis_trans),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DITOLAK => 'Ditolak',
            default => ucfirst((string) $this->status),
        };
    }

    public function getIsSetoranAttribute(): bool
    {
        return in_array($this->jenis_trans, [self::JENIS_SETORAN, self::JENIS_KOREKSI_SETORAN], true);
    }

    public function getIsPenarikanAttribute(): bool
    {
        return in_array($this->jenis_trans, [self::JENIS_PENARIKAN, self::JENIS_KOREKSI_PENARIKAN], true);
    }

    /**
     * Nominal bertanda: setoran positif, penarikan negatif.
     */
    public function getNominalBertandaAttribute(): int
    {
        return $this->is_setoran ? (int) $this->nominal : -((int) $this->nominal);
    }

    /**
     * Saldo rekening nasabah sebelum transaksi ini diproses.
     *
     * Dihitung dari ledger transaksi (tidak bergantung pada kolom saldo
     * saat ini) agar struk tetap benar walau sudah ada transaksi berikutnya.
     * Transaksi berstatus "ditolak" tidak dihitung karena tidak pernah
     * mengubah saldo rekening.
     */
    public function getSaldoSebelumAttribute(): int
    {
        if ($this->saldoSebelumCache !== null) {
            return $this->saldoSebelumCache;
        }

        $tanggal = $this->tanggal ?? $this->created_at?->startOfDay();

        if (! $tanggal) {
            return $this->saldoSebelumCache = 0;
        }

        $query = static::query()
            ->where('id_nasabah', $this->id_nasabah)
            ->where('status', '!=', self::STATUS_DITOLAK)
            ->where(function ($q) use ($tanggal) {
                $q->whereDate('tanggal', '<', $tanggal)
                    ->orWhere(function ($q) use ($tanggal) {
                        $q->whereDate('tanggal', $tanggal)->where('id', '<', $this->id);
                    });
            });

        return $this->saldoSebelumCache = (int) $query->sum(DB::raw(
            'CASE WHEN jenis_trans IN ("'.self::JENIS_SETORAN.'", "'.self::JENIS_KOREKSI_SETORAN.'")'
            .' THEN nominal ELSE -nominal END'
        ));
    }

    /**
     * Saldo rekening nasabah setelah transaksi ini diproses.
     */
    public function getSaldoSesudahAttribute(): int
    {
        if ($this->saldoSesudahCache === null) {
            $this->saldoSesudahCache = $this->saldo_sebelum + $this->nominal_bertanda;
        }

        return $this->saldoSesudahCache;
    }

    /**
     * Tanggal transaksi siap tampil (tanggal kolom transaksi, fallback created_at).
     */
    public function getTanggalTransaksiAttribute(): string
    {
        $tanggal = $this->tanggal ?? $this->created_at;

        return $tanggal ? $tanggal->format('d/m/Y') : '-';
    }

    public function getJamTransaksiAttribute(): string
    {
        $waktu = $this->created_at ?? $this->updated_at;

        if (! $waktu || $waktu->format('H:i:s') === '00:00:00') {
            return '-';
        }

        return $waktu->format('H:i:s');
    }

    /**
     * URL struk sesuai role pengguna aktif (null bila role tidak punya akses).
     */
    public function getStrukUrlAttribute(): ?string
    {
        return $this->routeStruk('struk');
    }

    public function getStrukCetakUrlAttribute(): ?string
    {
        return $this->routeStruk('struk.cetak');
    }

    public function getStrukPdfUrlAttribute(): ?string
    {
        return $this->routeStruk('struk.pdf');
    }

    protected function routeStruk(string $name): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $prefix = match ($user->role) {
            User::ROLE_TELLER => 'teller.',
            User::ROLE_SUPERVISOR => 'supervisor.',
            User::ROLE_ADMIN => 'admin.',
            default => null,
        };

        if (! $prefix || ! Gate::allows('viewStruk', $this)) {
            return null;
        }

        return route($prefix.$name, $this->id);
    }

    public function getBolehLihatStrukAttribute(): bool
    {
        return Gate::allows('viewStruk', $this);
    }

    public function getBolehCetakStrukAttribute(): bool
    {
        return Gate::allows('cetakStruk', $this);
    }

    public function getBolehUnduhStrukAttribute(): bool
    {
        return Gate::allows('unduhStruk', $this);
    }

    public function isKoreksi(): bool
    {
        return in_array($this->jenis_trans, [self::JENIS_KOREKSI_SETORAN, self::JENIS_KOREKSI_PENARIKAN], true);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
