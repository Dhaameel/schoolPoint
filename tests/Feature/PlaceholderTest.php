<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlaceholderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'username' => $role.'1',
        ]);
    }

    #[Test]
    public function the_admin_menu_pages_are_available(): void
    {
        $admin = $this->user('admin');

        foreach (['/students', '/teachers', '/classes', '/subjects', '/attendance', '/fees', '/reports'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertSuccessful();
        }
    }

    #[Test]
    public function the_teacher_menu_pages_are_available(): void
    {
        $teacher = $this->user('teacher');

        $this->actingAs($teacher)->get('/my-classes')->assertSuccessful();
        $this->actingAs($teacher)->get('/teacher/attendance')->assertSuccessful();
    }

    #[Test]
    public function roles_cannot_open_each_others_pages(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('teacher');

        $this->actingAs($teacher)->get('/students')->assertForbidden();
        $this->actingAs($teacher)->get('/settings')->assertForbidden();
        $this->actingAs($teacher)->get('/security')->assertForbidden();
        $this->actingAs($admin)->get('/my-classes')->assertForbidden();
        $this->actingAs($admin)->get('/teacher/attendance')->assertForbidden();
    }

    #[Test]
    public function the_sidebar_contains_the_role_items(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('teacher');

        $this->actingAs($admin)->get('/dashboard')
            ->assertSee('Students')
            ->assertSee('Teachers')
            ->assertSee('Classes')
            ->assertSee('Subjects')
            ->assertSee('Attendance')
            ->assertSee('Fees')
            ->assertSee('Reports')
            ->assertSee('Settings');

        $this->actingAs($teacher)->get('/dashboard')
            ->assertSee('My classes')
            ->assertSee('Attendance')
            ->assertDontSee('Fees')
            ->assertDontSee('Settings');
    }
}
