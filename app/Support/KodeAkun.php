<?php

namespace App\Support;

class KodeAkun
{
    public const KAS = 'Kas_101';

    public const BRANKAS = 'Brankas_102';

    public const TABUNGAN = 'Tabungan_201';

    public const LIST = [
        self::KAS => 'Kas',
        self::BRANKAS => 'Brankas',
        self::TABUNGAN => 'Tabungan',
    ];

    public static function label(?string $kode = null): string|array
    {
        if ($kode !== null) {
            return self::LIST[$kode] ?? $kode;
        }

        return self::LIST;
    }
}
