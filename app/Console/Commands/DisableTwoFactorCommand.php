<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Console\Command;

class DisableTwoFactorCommand extends Command
{
    protected $signature = 'app:disable-two-factor {username}';

    protected $description = 'Disable two-factor authentication for a user';

    public function handle(): int
    {
        $user = User::query()->where('username', $this->argument('username'))->first();

        if (! $user) {
            $this->error('No user found with that username.');

            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Audit::record('admins.two_factor_disabled', 'Two-factor authentication disabled from the command line.', [
            'username' => $user->username,
        ]);

        $this->info("Two-factor authentication disabled for {$user->username}.");

        return self::SUCCESS;
    }
}
