<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubjectTest extends TestCase
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
    public function an_admin_can_list_create_and_update_subjects(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/subjects')->assertSuccessful();

        $this->actingAs($admin)->post('/subjects', [
            'name' => 'Mathematics',
            'code' => 'MAT',
        ])->assertRedirect(route('subjects.index'));

        $subject = Subject::firstOrFail();

        $this->actingAs($admin)->put("/subjects/{$subject->id}", [
            'name' => 'Advanced Mathematics',
            'code' => 'MAT',
        ])->assertRedirect(route('subjects.index'));

        $this->assertSame('Advanced Mathematics', $subject->fresh()->name);
    }

    #[Test]
    public function subjects_can_be_searched_and_filtered(): void
    {
        $admin = $this->admin();
        Subject::create(['name' => 'Mathematics', 'code' => 'MAT']);
        Subject::create(['name' => 'English', 'code' => 'ENG']);

        $this->actingAs($admin)->get('/subjects?search=Math')
            ->assertSuccessful()
            ->assertSee('Mathematics')
            ->assertDontSee('English');

        $this->actingAs($admin)->get('/subjects?code=with')
            ->assertSuccessful()
            ->assertSee('Mathematics')
            ->assertSee('English');
    }

    #[Test]
    public function an_unassigned_subject_can_be_deleted(): void
    {
        $subject = Subject::create(['name' => 'Art', 'code' => 'ART']);

        $this->actingAs($this->admin())->delete("/subjects/{$subject->id}")
            ->assertRedirect(route('subjects.index'));

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }

    #[Test]
    public function a_teacher_cannot_manage_subjects(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'username' => 'teacher1']);

        $this->actingAs($teacher)->get('/subjects')->assertForbidden();
        $this->actingAs($teacher)->post('/subjects', ['name' => 'Art'])->assertForbidden();
    }
}
