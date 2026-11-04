<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\JadwalPetugas;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Inti pengelolaan Jadwal Petugas (admin/supervisor/teller/accounting/cs).
 */
class JadwalPetugasService
{
    public static function aturan(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Menyimpan jadwal baru/perbarui. Data user_id diresolusi sesuai peran
     * actor, lalu divalidasi relasi petugas + cek bentrok.
     */
    public static function simpan(User $aktor, array $data, ?JadwalPetugas $jadwal = null): JadwalPetugas
    {
        $data = self::resolusiPetugas($aktor, $data);

        $validator = Validator::make($data, self::aturan());

        $validator->after(function ($v) use ($data, $jadwal) {
            $userId = $data['user_id'] ?? null;

            if (! $userId) {
                $v->errors()->add('user_id', 'Petugas wajib dipilih.');

                return;
            }

            $petugas = User::find($userId);

            if (! $petugas || $petugas->status !== 'active') {
                $v->errors()->add('user_id', 'Akun petugas tidak aktif.');
            } elseif ($petugas->isNasabah()) {
                $v->errors()->add('user_id', 'Nasabah tidak memiliki jadwal kerja.');
            }

            if ($v->errors()->isEmpty()) {
                $bentrok = self::cekBentrok($data, $jadwal);
                if ($bentrok) {
                    $v->errors()->add('jam_mulai', 'Sudah ada jadwal petugas pada rentang jam yang sama.');
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $mulai = self::normalisasiJam($data['jam_mulai']);
        $selesai = self::normalisasiJam($data['jam_selesai']);

        return DB::transaction(function () use ($aktor, $data, $jadwal, $mulai, $selesai) {
            $payload = [
                'user_id' => $data['user_id'] ?? null,
                'tanggal' => $data['tanggal'],
                'hari' => Carbon::parse($data['tanggal'])->translatedFormat('l'),
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
                'keterangan' => $data['keterangan'] ?? null,
            ];

            if ($jadwal) {
                $jadwal->fill($payload);
                $jadwal->save();
                $label = 'ubah_jadwal_petugas';
                $deskripsi = 'Jadwal petugas tanggal '.$data['tanggal'].' ('.$mulai.'-'.$selesai.') diperbarui.';
                $baru = false;
            } else {
                $payload['status'] = JadwalPetugas::STATUS_DIJADWALKAN;
                $payload['created_by'] = $aktor->id;
                $jadwal = JadwalPetugas::create($payload);
                $label = 'tambah_jadwal_petugas';
                $deskripsi = 'Jadwal petugas tanggal '.$data['tanggal'].' ('.$mulai.'-'.$selesai.') dijadwalkan.';
                $baru = true;
            }

            ActivityLog::catat($aktor->id, $label, $deskripsi);

            self::notifikasi($jadwal, $aktor, $baru);

            return $jadwal;
        });
    }

    /**
     * Resolusi user_id sesuai peran pelaku.
     */
    protected static function resolusiPetugas(User $aktor, array $data): array
    {
        $data['user_id'] = $data['user_id'] ?? null;

        if ($aktor->isAdmin()) {
            unset($data['bagian']);

            return $data;
        }

        $data['user_id'] = $aktor->id;

        return $data;
    }

    protected static function cekBentrok(array $data, ?JadwalPetugas $abaikan = null): bool
    {
        $mulai = self::normalisasiJam($data['jam_mulai']);
        $selesai = self::normalisasiJam($data['jam_selesai']);

        $query = JadwalPetugas::whereDate('tanggal', $data['tanggal'])
            ->where('status', '!=', JadwalPetugas::STATUS_DIBATALKAN);

        if (! empty($data['user_id'])) {
            $query->where('user_id', $data['user_id']);
        }

        if ($abaikan) {
            $query->where('id', '!=', $abaikan->id);
        }

        return $query->where(function ($q) use ($mulai, $selesai) {
            $q->where(function ($q2) use ($mulai, $selesai) {
                $q2->where('jam_mulai', '<', $selesai)
                    ->where('jam_selesai', '>', $mulai);
            })->orWhere(function ($q2) use ($mulai, $selesai) {
                $q2->where('jam_mulai', $mulai)
                    ->where('jam_selesai', $selesai);
            });
        })->exists();
    }

    /**
     * Notifikasi hanya ke role terkait (bukan semua role).
     */
    protected static function notifikasi(JadwalPetugas $jadwal, User $aktor, bool $baru): void
    {
        $petugas = $jadwal->user;
        $url = self::routeJadwalUntuk($petugas);

        if ($petugas && $petugas->id !== $aktor->id) {
            UserNotification::kirim(
                $petugas,
                UserNotification::TIPE_INFO,
                $baru ? 'Jadwal Petugas Baru' : 'Jadwal Petugas Diperbarui',
                "Anda dijadwalkan pada {$jadwal->tanggal->format('d M Y')} ({$jadwal->jam_label}) sebagai {$jadwal->bagian}. Status: {$jadwal->status_label}.",
                $url,
            );
        }

        if ($baru) {
            $supervisor = User::where('role', User::ROLE_SUPERVISOR)
                ->where('status', 'active')
                ->get();

            foreach ($supervisor as $spv) {
                UserNotification::kirim(
                    $spv,
                    UserNotification::TIPE_INFO,
                    'Jadwal Petugas Terdaftar',
                    "Jadwal baru {$jadwal->bagian} ({$jadwal->nama_petugas}) pada {$jadwal->tanggal->format('d M Y')} {$jadwal->jam_label} oleh {$aktor->nama_petugas}.",
                    route('supervisor.jadwal.index'),
                );
            }
        }
    }

    public static function routeJadwalUntuk(?User $petugas): ?string
    {
        if (! $petugas || $petugas->isNasabah()) {
            return null;
        }

        return match (true) {
            $petugas->isAdmin() => route('admin.jadwal.index'),
            $petugas->isTeller() => route('teller.jadwal.index'),
            default => route('supervisor.jadwal.index'),
        };
    }

    protected static function normalisasiJam(mixed $jam): mixed
    {
        if (! is_string($jam)) {
            return $jam;
        }

        return preg_match('/^\d{2}:\d{2}$/', $jam) ? $jam.':00' : $jam;
    }
}
