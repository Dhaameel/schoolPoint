<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'username' => 'admin']);
    }

    private function csv(array $rows): UploadedFile
    {
        $header = "first_name,last_name,gender,date_of_birth,guardian_name,guardian_phone,admission_number,class_name,status\n";
        $body = '';

        foreach ($rows as $row) {
            $body .= implode(',', $row)."\n";
        }

        return UploadedFile::fake()->createWithContent('students.csv', $header.$body);
    }

    #[Test]
    public function the_import_page_and_template_are_available(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/students/import')->assertSuccessful();
        $this->actingAs($admin)->get('/students/import/template')
            ->assertSuccessful()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    #[Test]
    public function a_valid_csv_can_be_previewed_and_imported(): void
    {
        $admin = $this->admin();
        $file = $this->csv([
            ['Amina', 'Yusuf', 'Female', '2015-04-12', 'Hodan', '0612345678', 'ADM-001', 'Class 1', 'active'],
        ]);

        $this->actingAs($admin)->post('/students/import/preview', ['file' => $file])
            ->assertSuccessful()
            ->assertSee('Amina')
            ->assertSee('Valid rows');

        $this->actingAs($admin)->post('/students/import/store')
            ->assertRedirect(route('students.index'));

        $this->assertSame(1, Student::count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'students.imported']);
    }

    #[Test]
    public function invalid_rows_save_nothing_unless_bad_rows_are_skipped(): void
    {
        $admin = $this->admin();
        $file = $this->csv([
            ['Amina', 'Yusuf', 'Female', '2015-04-12', 'Hodan', '0612345678', 'ADM-001', 'Class 1', 'active'],
            ['Hodan', 'Ali', 'Female', '2015-04-12', 'Sahra', '0611111111', 'ADM-002', 'Missing Class', 'active'],
        ]);

        $this->actingAs($admin)->post('/students/import/preview', ['file' => $file])
            ->assertSuccessful()
            ->assertSee('Missing Class')
            ->assertSee('does not exist');

        $this->actingAs($admin)->post('/students/import/store')
            ->assertSessionHas('error');

        $this->assertSame(0, Student::count());

        $this->actingAs($admin)->post('/students/import/store', ['skip_bad_rows' => 1])
            ->assertRedirect(route('students.index'));

        $this->assertSame(1, Student::count());
        $this->assertDatabaseHas('students', ['first_name' => 'Amina']);
    }

    #[Test]
    public function a_bad_header_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('bad.csv', "name,class\nAmina,Class 1\n");

        $this->actingAs($this->admin())->post('/students/import/preview', ['file' => $file])
            ->assertSessionHas('error');
    }

    #[Test]
    public function a_teacher_cannot_import_students(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'username' => 'teacher1']);

        $this->actingAs($teacher)->get('/students/import')->assertForbidden();
        $this->actingAs($teacher)->post('/students/import/preview', [])->assertForbidden();
    }
}
