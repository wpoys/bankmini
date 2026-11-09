<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brankas extends Model
{
    protected $table = 'brankas';

    protected $guarded = ['id'];

    public static function saldo(): int
    {
        return (int) (static::query()->find(1)?->saldo ?? 0);
    }
}
