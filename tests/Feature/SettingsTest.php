<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'username' => 'owner']);
    }

    #[Test]
    public function an_admin_can_open_settings(): void
    {
        $this->actingAs($this->admin())->get('/settings')
            ->assertSuccessful()
            ->assertSee('School name')
            ->assertSee('Timezone');
    }

    #[Test]
    public function a_teacher_cannot_open_settings(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'username' => 'teacher1']);

        $this->actingAs($teacher)->get('/settings')->assertForbidden();
    }

    #[Test]
    public function settings_are_saved_and_the_timezone_is_applied(): void
    {
        $this->actingAs($this->admin())->put('/settings', [
            'school_name' => 'Green Hill School',
            'currency' => 'KES',
            'student_id_prefix' => 'GHS',
            'receipt_prefix' => 'RCP',
            'timezone' => 'Africa/Nairobi',
            'school_days' => [1, 2, 3, 4, 5],
        ])->assertRedirect(route('settings.edit'));

        $this->assertSame('Green Hill School', Setting::get('school_name'));
        $this->assertSame('KES', Setting::get('currency'));
        $this->assertSame('Africa/Nairobi', Setting::get('timezone'));
        $this->assertSame('Africa/Nairobi', config('app.timezone'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    #[Test]
    public function the_timezone_read_is_cached(): void
    {
        Setting::put('timezone', 'Africa/Nairobi');

        $this->assertSame('Africa/Nairobi', Cache::get('settings.timezone'));
    }

    #[Test]
    public function an_invalid_timezone_is_rejected(): void
    {
        $this->actingAs($this->admin())->put('/settings', [
            'school_name' => 'Green Hill School',
            'currency' => 'KES',
            'student_id_prefix' => 'GHS',
            'receipt_prefix' => 'RCP',
            'timezone' => 'Mars/Olympus',
            'school_days' => [1, 2, 3, 4, 5],
        ])->assertSessionHasErrors('timezone');
    }
}
