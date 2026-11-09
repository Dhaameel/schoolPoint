<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'username' => 'admin']);
    }

    #[Test]
    public function an_admin_can_create_a_teacher_and_see_the_temporary_password_once(): void
    {
        $response = $this->actingAs($this->admin())->post('/teachers', [
            'name' => 'Amina Yusuf',
            'username' => 'amina',
            'phone' => '0612345678',
            'email' => 'amina@example.com',
        ])->assertRedirect(route('teachers.slip'));

        $response->assertSessionHas('teacher_credentials');
        $temporaryPassword = session('teacher_credentials.password');

        $teacher = Teacher::firstOrFail();
        $user = $teacher->user;

        $this->assertSame('teacher', $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertSame('amina', $user->username);
        $this->assertDatabaseHas('audit_logs', ['action' => 'teachers.created']);

        $this->get('/teachers/slip')
            ->assertSuccessful()
            ->assertSee('amina')
            ->assertSee($temporaryPassword);

        $this->get('/teachers/slip')->assertRedirect(route('teachers.index'));
    }

    #[Test]
    public function an_admin_can_reset_a_teacher_password(): void
    {
        $teacher = Teacher::create([
            'user_id' => User::factory()->create([
                'role' => 'teacher',
                'username' => 'amina',
            ])->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin())->post("/teachers/{$teacher->id}/reset-password")
            ->assertRedirect(route('teachers.slip'))
            ->assertSessionHas('teacher_credentials');

        $this->assertTrue($teacher->fresh()->user->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['action' => 'teachers.password_reset']);
    }

    #[Test]
    public function a_deactivated_teacher_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'username' => 'amina',
            'password' => 'password',
        ]);
        $teacher = Teacher::create(['user_id' => $user->id, 'status' => 'active']);

        $this->actingAs($this->admin())->post("/teachers/{$teacher->id}/deactivate")
            ->assertSessionHas('status');

        $this->assertFalse($user->fresh()->is_active);

        $this->post('/logout');

        $this->post('/login', ['username' => 'amina', 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function an_admin_can_reactivate_a_teacher(): void
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'username' => 'amina',
            'is_active' => false,
        ]);
        $teacher = Teacher::create(['user_id' => $user->id, 'status' => 'inactive']);

        $this->actingAs($this->admin())->post("/teachers/{$teacher->id}/reactivate")
            ->assertSessionHas('status');

        $this->assertTrue($user->fresh()->is_active);
        $this->assertSame('active', $teacher->fresh()->status);
    }

    #[Test]
    public function an_admin_can_update_a_teacher(): void
    {
        $user = User::factory()->create(['role' => 'teacher', 'username' => 'amina']);
        $teacher = Teacher::create(['user_id' => $user->id, 'status' => 'active']);

        $this->actingAs($this->admin())->put("/teachers/{$teacher->id}", [
            'name' => 'Amina Yusuf',
            'username' => 'amina.y',
            'phone' => '0611111111',
            'email' => 'amina.y@example.com',
        ])->assertRedirect(route('teachers.show', $teacher));

        $this->assertSame('Amina Yusuf', $user->fresh()->name);
        $this->assertSame('amina.y', $user->fresh()->username);
        $this->assertSame('0611111111', $teacher->fresh()->phone);
    }

    #[Test]
    public function a_teacher_cannot_manage_teachers(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'username' => 'amina']);

        $this->actingAs($teacher)->get('/teachers')->assertForbidden();
        $this->actingAs($teacher)->post('/teachers', ['name' => 'X', 'username' => 'x'])->assertForbidden();
    }
}
