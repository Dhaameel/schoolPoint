<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'username' => 'owner',
            'password' => 'password',
            'role' => 'admin',
        ], $attributes));
    }

    #[Test]
    public function the_two_factor_setup_page_draws_a_qr_code(): void
    {
        $this->actingAs($this->admin())
            ->get(route('security.two-factor.setup'))
            ->assertSuccessful()
            ->assertSee('data:image/svg+xml;base64', false);
    }

    #[Test]
    public function an_admin_can_complete_two_factor_setup(): void
    {
        $user = $this->admin();
        $secret = (new Google2FA())->generateSecretKey();

        $this->actingAs($user)
            ->withSession(['two_factor_setup_secret' => $secret])
            ->post(route('security.two-factor.enable'), [
                'code' => (new Google2FA())->getCurrentOtp($secret),
            ])
            ->assertRedirect(route('security.index'))
            ->assertSessionHas('recovery_codes');

        $user->refresh();

        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
        $this->assertCount(8, $user->two_factor_recovery_codes);
    }

    #[Test]
    public function login_stops_at_the_two_factor_challenge(): void
    {
        $secret = (new Google2FA())->generateSecretKey();
        $user = $this->admin([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', [
            'username' => 'owner',
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();

        $this->post(route('two-factor.verify'), [
            'code' => (new Google2FA())->getCurrentOtp($secret),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function a_recovery_code_can_be_used_once(): void
    {
        $user = $this->admin([
            'two_factor_secret' => (new Google2FA())->generateSecretKey(),
            'two_factor_recovery_codes' => ['AAAA-BBBB', 'CCCC-DDDD'],
            'two_factor_confirmed_at' => now(),
        ]);

        $this->withSession(['two_factor_user_id' => $user->id])
            ->post(route('two-factor.verify'), ['code' => 'AAAA-BBBB'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(['CCCC-DDDD'], $user->fresh()->two_factor_recovery_codes);

        $this->post('/logout');

        $this->withSession(['two_factor_user_id' => $user->id])
            ->post(route('two-factor.verify'), ['code' => 'AAAA-BBBB'])
            ->assertSessionHasErrors('code');
    }

    #[Test]
    public function an_admin_can_disable_two_factor(): void
    {
        $user = $this->admin([
            'two_factor_secret' => (new Google2FA())->generateSecretKey(),
            'two_factor_recovery_codes' => ['AAAA-BBBB'],
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('security.two-factor.disable'), ['password' => 'password'])
            ->assertRedirect(route('security.index'));

        $user->refresh();

        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
        $this->assertNull($user->two_factor_secret);
    }
}
