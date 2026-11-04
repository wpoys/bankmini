<?php

namespace App\Console\Commands;

use App\Models\Nasabah;
use Illuminate\Console\Command;

class BackfillNasabahUser extends Command
{
    protected $signature = 'nasabah:backfill-user';

    protected $description = 'Buat akun user untuk nasabah yang belum memiliki akun';

    public function handle(): int
    {
        $nasabah = Nasabah::with('user')->whereNull('user_id')->get();

        foreach ($nasabah as $item) {
            $item->ensureUser();
        }

        $this->info("Selesai. {$nasabah->count()} akun nasabah dibuat.");

        return self::SUCCESS;
    }
}
