<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/** Jadikan akun admin (boleh membuka /admin), mis. setelah make:filament-user. */
class MakeAdmin extends Command
{
    protected $signature = 'user:admin {email} {--remove : cabut akses admin}';

    protected $description = 'Beri (atau cabut) akses /admin untuk akun dengan email ini';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('Tidak ada akun dengan email itu.');

            return self::FAILURE;
        }
        $user->forceFill(['is_admin' => ! $this->option('remove')])->save();
        $this->info($user->email.($user->is_admin ? ' sekarang admin.' : ' bukan admin lagi.'));

        return self::SUCCESS;
    }
}
