<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Buat / perbarui user login CRM (sistem tertutup, tanpa registrasi publik).
 *
 *   php artisan user:create email@domain.com "kataSandi" --name="Nama"
 */
class CreateUser extends Command
{
    protected $signature = 'user:create {email} {password} {--name=Admin}';

    protected $description = 'Buat atau perbarui user login CRM';

    public function handle(): int
    {
        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => (string) $this->option('name'), 'password' => $this->argument('password')],
        );

        $this->info("User '{$user->email}' siap dipakai login (nama: {$user->name}).");

        return self::SUCCESS;
    }
}
