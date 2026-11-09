<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FreshInstallTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function migrate_seed_creates_settings_but_no_users(): void
    {
        $this->seed();

        $this->assertSame(0, User::query()->count());
        $this->assertNotNull(Setting::get('school_name'));
        $this->assertSame('SP', Setting::get('student_id_prefix'));
        $this->assertSame('RCPT', Setting::get('receipt_prefix'));
    }
}
