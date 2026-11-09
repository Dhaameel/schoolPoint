<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'username' => 'admin',
            'password' => 'password',
            'role' => 'admin',
        ], $attributes));
    }

    #[Test]
    public function guests_can_see_the_login_page(): void
    {
        $this->get('/login')->assertSuccessful()->assertSee('Sign in to your school');
    }

    #[Test]
    public function a_user_can_log_in_with_a_username(): void
    {
        $user = $this->user();

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function email_is_not_a_login_identifier(): void
    {
        $this->user(['email' => 'admin@example.com']);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function an_inactive_user_cannot_log_in(): void
    {
        $this->user(['is_active' => false]);

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function login_attempts_are_rate_limited(): void
    {
        $this->user();
        RateLimiter::clear('login:admin|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'admin', 'password' => 'wrong']);
        }

        $this->post('/login', ['username' => 'admin', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function a_user_can_log_out(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
