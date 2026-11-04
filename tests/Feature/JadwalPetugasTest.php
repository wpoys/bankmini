<?php

use App\Models\JadwalPetugas;
use App\Models\User;
use App\Support\JadwalPetugasService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('menampilkan halaman jadwal untuk semua role non-nasabah', function () {
    $users = [
        'admin' => 'admin',
        'supervisor' => 'supervisor',
        'teller1' => 'teller',
    ];

    foreach ($users as $username => $pageRole) {
        $this->actingAs(User::where('username', $username)->first())
            ->get("/{$pageRole}/jadwal")
            ->assertOk();
    }
});

it('halaman kelola sub teller sudah dihapus', function () {
    $admin = User::where('username', 'admin')->first();

    $this->actingAs($admin)
        ->get('/admin/sub-teller')
        ->assertNotFound();
});

it('supervisor tidak bisa membuka form tambah jadwal', function () {
    $supervisor = User::where('username', 'supervisor')->first();

    $this->actingAs($supervisor)
        ->get('/supervisor/jadwal/create')
        ->assertNotFound();
});

it('teller dapat membuat jadwal pribadi', function () {
    $teller = User::where('username', 'teller1')->first();

    $this->actingAs($teller)
        ->post('/teller/jadwal', [
            'tanggal' => today()->toDateString(),
            'jam_mulai' => '13:00',
            'jam_selesai' => '16:00',
            'keterangan' => 'Jaga sendiri',
        ])
        ->assertRedirect(route('teller.jadwal.index'));

    $jadwal = JadwalPetugas::where('user_id', $teller->id)->first();
    expect($jadwal)->not->toBeNull();
    expect($jadwal->nama_petugas)->toBe($teller->nama_petugas);
});

it('menolak jadwal dengan jam selesai sebelum jam mulai', function () {
    $teller = User::where('username', 'teller1')->first();

    JadwalPetugasService::simpan($teller, [
        'tanggal' => today()->toDateString(),
        'jam_mulai' => '14:00',
        'jam_selesai' => '09:00',
    ]);
})->throws(ValidationException::class);

it('teller hanya dapat mengelola jadwalnya sendiri dan admin dapat mengeditnya', function () {
    $teller = User::where('username', 'teller1')->first();
    $tellerLain = User::where('username', 'teller2')->first();
    $admin = User::where('username', 'admin')->first();

    $this->actingAs($teller)
        ->post('/teller/jadwal', [
            'tanggal' => today()->toDateString(),
            'jam_mulai' => '10:00',
            'jam_selesai' => '13:00',
        ])
        ->assertRedirect(route('teller.jadwal.index'));

    $jadwal = JadwalPetugas::where('user_id', $teller->id)->first();
    expect($jadwal)->not->toBeNull();

    $this->actingAs($tellerLain)
        ->get("/teller/jadwal/{$jadwal->id}/edit")
        ->assertForbidden();

    $this->actingAs($admin)
        ->get("/admin/jadwal/{$jadwal->id}/edit")
        ->assertOk();
});

it('supervisor hanya bisa membaca jadwal (tanpa aksi kelola)', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller = User::where('username', 'teller1')->first();

    $this->actingAs($teller)
        ->post('/teller/jadwal', [
            'tanggal' => today()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '12:00',
            'keterangan' => 'Piket',
        ]);

    $this->actingAs($supervisor)
        ->get('/supervisor/jadwal')
        ->assertOk()
        ->assertSee('Jadwal Petugas');
});
