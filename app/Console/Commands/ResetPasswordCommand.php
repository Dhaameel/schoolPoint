<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;

class ResetPasswordCommand extends Command
{
    protected $signature = 'app:reset-password {username} {--password= : New password}';

    protected $description = 'Reset a user password by username';

    public function handle(): int
    {
        $user = User::query()->where('username', $this->argument('username'))->first();

        if (! $user) {
            $this->error('No user found with that username.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('New password');

        if ($password !== null && ! $this->option('password')) {
            $confirmation = $this->secret('Confirm new password');

            if ($password !== $confirmation) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
        ])->save();

        Audit::record('admins.password_reset', 'Password reset from the command line.', [
            'username' => $user->username,
        ]);

        $this->info("Password reset for {$user->username}. The user must change it at next login.");

        return self::SUCCESS;
    }
}
