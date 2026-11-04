<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $username
 * @property string|null $email
 * @property string $password
 * @property string $nama_petugas
 * @property string $role
 * @property string $status
 * @property string|null $fcm_token
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $role_label
 * @property-read Nasabah|null $nasabah
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Transaksi> $transaksi
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LaporanHarian> $laporanHarian
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LaporanHarian> $approvedLaporan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ActivityLog> $activityLogs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserNotification> $userNotifications
 */
#[Fillable(['username', 'email', 'password', 'nama_petugas', 'role', 'status', 'status_aktivasi', 'fcm_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_TELLER = 'teller';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_NASABAH = 'nasabah';

    public const DEFAULT_PASSWORD = 'Nasabah@2026';

    public const AKTIVASI_BELUM_AKTIF = 'belum_aktif';

    public const AKTIVASI_AKTIF = 'aktif';

    public const ROLE_OPTION = [
        'admin' => 'Administrator',
        'teller' => 'Teller',
        'supervisor' => 'Supervisor',
        'nasabah' => 'Nasabah',
    ];

    public const ROLE_STAFF = [self::ROLE_ADMIN, self::ROLE_TELLER, self::ROLE_SUPERVISOR];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'kode_aktivasi_kadaluarsa' => 'datetime',
            'aktivasi_dikunci_sampai' => 'datetime',
            'aktivasi_kirim_ulang_sampai' => 'datetime',
            'token_aktivasi_kadaluarsa' => 'datetime',
            'percobaan_aktivasi' => 'integer',
            'jumlah_kirim_ulang' => 'integer',
        ];
    }

    public function nasabah(): HasOne
    {
        return $this->hasOne(Nasabah::class, 'user_id');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_user');
    }

    public function laporanHarian(): HasMany
    {
        return $this->hasMany(LaporanHarian::class, 'teller_id');
    }

    public function approvedLaporan(): HasMany
    {
        return $this->hasMany(LaporanHarian::class, 'approved_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'id_user');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeller(): bool
    {
        return $this->role === self::ROLE_TELLER;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, self::ROLE_STAFF, true);
    }

    public function isNasabah(): bool
    {
        return $this->role === self::ROLE_NASABAH;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLE_OPTION[$this->role] ?? ucfirst($this->role);
    }
}
