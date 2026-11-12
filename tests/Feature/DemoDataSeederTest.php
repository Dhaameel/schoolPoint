<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_demo_school(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->assertSame(3, SchoolClass::count());
        $this->assertSame(5, Subject::count());
        $this->assertSame(4, Teacher::count());
        $this->assertSame(30, Student::count());
        $this->assertSame(15, ClassSubject::count());
        $this->assertSame(30, Student::distinct()->count('student_id'));
        $this->assertSame(9, FeeStructure::count());
        $this->assertSame(60, Fee::count());
    }

    #[Test]
    public function it_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->expectException(\RuntimeException::class);

        app(DemoDataSeeder::class)->run();
    }
}
