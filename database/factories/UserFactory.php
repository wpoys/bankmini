<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'nama_petugas' => fake()->name(),
            'role' => fake()->randomElement(['admin', 'teller', 'supervisor', 'nasabah']),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }
}
