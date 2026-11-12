<?php

namespace Tests\Feature;

use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\DatabaseError;
use Database\Seeders\SettingSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeeConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The fee lock test requires MySQL.');
        }
    }

    #[Test]
    public function two_connections_cannot_lock_the_same_fee_at_once(): void
    {
        $this->seed(SettingSeeder::class);

        $class = SchoolClass::create(['name' => 'Class 1', 'status' => 'active']);
        $student = Student::create([
            'student_id' => 'SP-2026-000001',
            'first_name' => 'Amina',
            'last_name' => 'Yusuf',
            'guardian_name' => 'Hodan',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
            'status' => 'active',
            'student_year' => 2026,
            'student_sequence' => 1,
        ]);

        $fee = Fee::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'title' => 'Tuition',
            'amount' => '100.00',
            'amount_paid' => '0.00',
            'currency' => 'USD',
            'status' => 'unpaid',
            'billed_at' => now(),
        ]);

        config(['database.connections.mysql_second' => config('database.connections.mysql')]);
        DB::purge('mysql_second');

        $second = DB::connection('mysql_second');
        $second->statement('SET SESSION innodb_lock_wait_timeout = 1');

        DB::beginTransaction();
        DB::table('fees')->where('id', $fee->id)->lockForUpdate()->first();

        try {
            $second->beginTransaction();
            $second->table('fees')->where('id', $fee->id)->lockForUpdate()->first();

            $this->fail('The second connection acquired a fee row lock while the first connection held it.');
        } catch (QueryException $exception) {
            $this->assertTrue(
                DatabaseError::isLockWaitTimeout($exception),
                'Expected a MySQL lock wait timeout, got: '.$exception->getMessage()
            );
        } finally {
            if ($second->transactionLevel() > 0) {
                $second->rollBack();
            }

            DB::rollBack();
        }

        $second->beginTransaction();
        $row = $second->table('fees')->where('id', $fee->id)->lockForUpdate()->first();
        $this->assertNotNull($row);
        $this->assertSame('unpaid', $row->status);
        $second->rollBack();

        $this->assertSame('100.00', $fee->fresh()->amount);
    }
}
