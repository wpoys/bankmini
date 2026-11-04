<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('menu profil staff hanya bisa diakses user yang sudah login', function () {
    $this->get(route('profil'))->assertRedirect(route('login'));
});

it('menu profil staff bisa diakses seluruh role staff', function (string $role) {
    $staff = User::where('role', $role)->firstOrFail();

    $this->actingAs($staff)
        ->get(route('profil'))
        ->assertOk()
        ->assertSee('Pengaturan Profil');
})->with(['admin', 'teller', 'supervisor']);

it('nasabah tidak dapat mengakses halaman profil staff', function () {
    $nasabah = User::where('role', 'nasabah')->firstOrFail();

    $this->actingAs($nasabah)
        ->get(route('profil'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');
});

it('staff dapat memperbarui nama dan email sendiri', function () {
    $supervisor = User::where('role', 'supervisor')->firstOrFail();

    $this->actingAs($supervisor)
        ->put(route('profil.update'), [
            'nama_petugas' => 'Supervisor Baru',
            'email' => 'supervisor.baru@contoh.sch.id',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($supervisor->fresh()->nama_petugas)->toBe('Supervisor Baru');
    expect($supervisor->fresh()->email)->toBe('supervisor.baru@contoh.sch.id');
});

it('validasi nama wajib diisi pada update profil', function () {
    $admin = User::where('role', 'admin')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('profil'))
        ->put(route('profil.update'), ['nama_petugas' => ''])
        ->assertSessionHasErrors('nama_petugas');
});

it('email tidak boleh bertabrakan dengan user lain', function () {
    $supervisor = User::where('role', 'supervisor')->firstOrFail();
    $teller = User::where('role', 'teller')->firstOrFail();

    $this->actingAs($supervisor)
        ->put(route('profil.update'), [
            'nama_petugas' => $supervisor->nama_petugas,
            'email' => $teller->email,
        ])
        ->assertSessionHasErrors('email');
});
