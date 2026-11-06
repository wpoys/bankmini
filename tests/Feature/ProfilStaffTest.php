<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('halaman profil khusus tidak tersedia', function () {
    $this->get('/profil')->assertNotFound();
});

it('endpoint pembaruan profil tidak tersedia', function () {
    $this->put('/profil', [
        'nama_petugas' => 'Nama Baru',
        'email' => 'baru@example.test',
    ])->assertNotFound();
});
