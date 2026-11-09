<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    #[Test]
    public function the_create_admin_command_creates_an_admin(): void
    {
        $this->artisan('app:create-admin', [
            '--name' => 'School Owner',
            '--username' => 'owner',
            '--password' => 'strong-password',
        ])->assertSuccessful();

        $user = User::where('username', 'owner')->firstOrFail();

        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('strong-password', $user->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'admins.created']);
    }

    #[Test]
    public function the_reset_password_command_sets_a_temporary_password(): void
    {
        $user = User::factory()->create(['username' => 'teacher1', 'role' => 'teacher']);

        $this->artisan('app:reset-password', [
            'username' => 'teacher1',
            '--password' => 'new-temporary-pass',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('new-temporary-pass', $user->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'admins.password_reset']);
    }

    #[Test]
    public function the_disable_two_factor_command_clears_the_factor(): void
    {
        $user = User::factory()->create([
            'username' => 'owner',
            'role' => 'admin',
            'two_factor_secret' => 'SECRET',
            'two_factor_recovery_codes' => ['AAAA-BBBB'],
            'two_factor_confirmed_at' => now(),
        ]);

        $this->artisan('app:disable-two-factor', ['username' => 'owner'])->assertSuccessful();

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admins.two_factor_disabled']);
    }
}
