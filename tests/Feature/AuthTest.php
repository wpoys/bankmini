<?php

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('redirects guest to login page', function () {
    $this->get('/')
        ->assertRedirect(route('login'));

    $this->get('/admin/dashboard')
        ->assertRedirect(route('login'));
});

it('logs in admin and redirects to admin dashboard', function () {
    $this->post('/login', [
        'username' => 'admin',
        'password' => 'Admin@2026',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs(User::where('username', 'admin')->first());
});

it('logs in teller and redirects to teller dashboard', function () {
    $this->post('/login', [
        'username' => 'teller1',
        'password' => 'Teller@2026',
    ])->assertRedirect(route('teller.dashboard'));

    $this->assertAuthenticatedAs(User::where('username', 'teller1')->first());
});

it('logs in supervisor and redirects to supervisor dashboard', function () {
    $this->post('/login', [
        'username' => 'supervisor',
        'password' => 'Super@2026',
    ])->assertRedirect(route('supervisor.dashboard'));

    $this->assertAuthenticatedAs(User::where('username', 'supervisor')->first());
});

it('rejects nasabah login from web portal', function () {
    $this->post('/login', [
        'username' => '20241001',
        'password' => 'Nasabah@2026',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('rejects an existing nasabah session from web', function () {
    $nasabah = User::where('username', '20241001')->firstOrFail();

    $this->actingAs($nasabah)
        ->get(route('notifikasi.index'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('rejects wrong password with error', function () {
    $this->post('/login', [
        'username' => 'admin',
        'password' => 'salah123',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('rejects inactive user with error', function () {
    User::where('username', 'admin')->update(['status' => 'inactive']);

    $this->post('/login', [
        'username' => 'admin',
        'password' => 'Admin@2026',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('rejects missing credentials', function () {
    $this->post('/login', ['username' => '', 'password' => ''])
        ->assertSessionHasErrors(['username', 'password']);
});

it('logs out authenticated user', function () {
    $admin = User::where('username', 'admin')->first();

    $this->actingAs($admin)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
