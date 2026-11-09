<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Admin display name}
        {--username= : Login username}
        {--password= : Password}';

    protected $description = 'Create an administrator account';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $username = $this->option('username') ?: $this->ask('Username');
        $password = $this->option('password') ?: $this->secret('Password');

        if ($password !== null && ! $this->option('password')) {
            $confirmation = $this->secret('Confirm password');

            if ($password !== $confirmation) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', (string) $username)) {
            $this->error('Username may only contain letters, numbers, dots, underscores and dashes.');

            return self::FAILURE;
        }

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        if (User::query()->where('username', $username)->exists()) {
            $this->error('That username is already in use.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'role' => 'admin',
            'must_change_password' => false,
            'is_active' => true,
        ]);

        Audit::record('admins.created', 'Administrator created from the command line.', [
            'username' => $user->username,
        ]);

        $this->info("Administrator {$user->username} created.");

        return self::SUCCESS;
    }
}
