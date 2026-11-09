<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    private function user(bool $mustChange = false): User
    {
        return User::factory()->create([
            'username' => 'teacher1',
            'password' => 'temporary-password',
            'role' => 'teacher',
            'must_change_password' => $mustChange,
        ]);
    }

    #[Test]
    public function a_temporary_password_forces_a_password_change(): void
    {
        $user = $this->user(true);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
        $this->actingAs($user)->get('/password/change')->assertSuccessful();
    }

    #[Test]
    public function a_user_can_change_their_password(): void
    {
        $user = $this->user(true);

        $this->actingAs($user)->post('/password/change', [
            'current_password' => 'temporary-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->actingAs($user)->get('/dashboard')->assertSuccessful();
    }

    #[Test]
    public function the_current_password_must_be_correct(): void
    {
        $user = $this->user(true);

        $this->actingAs($user)->post('/password/change', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');
    }
}
