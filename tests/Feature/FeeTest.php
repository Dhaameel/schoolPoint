<?php

namespace Tests\Feature;

use App\Actions\Fees\RecordFeePaymentAction;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\FeeBalance;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-11-12 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'username' => 'admin'.Str::lower(Str::random(6))]);
    }

    private function teacher(): User
    {
        $user = User::factory()->create(['role' => 'teacher', 'username' => 'teacher'.Str::lower(Str::random(6))]);
        Teacher::create(['user_id' => $user->id, 'status' => 'active']);

        return $user;
    }

    private function class(string $name = 'Class 1'): SchoolClass
    {
        return SchoolClass::create(['name' => $name, 'status' => 'active']);
    }

    private function student(SchoolClass $class, string $name = 'Amina', string $status = 'active'): Student
    {
        static $sequence = 0;
        $sequence++;

        return Student::create([
            'student_id' => 'SP-2026-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'first_name' => $name,
            'last_name' => 'Yusuf',
            'guardian_name' => 'Hodan',
            'guardian_phone' => '0612345678',
            'class_id' => $class->id,
            'status' => $status,
            'student_year' => 2026,
            'student_sequence' => $sequence,
        ]);
    }

    private function structure(SchoolClass $class, string $name = 'Tuition', string $amount = '100.00', bool $active = true): FeeStructure
    {
        return FeeStructure::create([
            'class_id' => $class->id,
            'name' => $name,
            'amount' => $amount,
            'currency' => 'USD',
            'is_active' => $active,
        ]);
    }

    private function fee(Student $student, string $amount = '100.00', ?FeeStructure $structure = null): Fee
    {
        return Fee::create([
            'student_id' => $student->id,
            'class_id' => $student->class_id,
            'fee_structure_id' => $structure?->id,
            'title' => $structure?->name ?? 'Tuition',
            'amount' => $amount,
            'amount_paid' => '0.00',
            'currency' => 'USD',
            'status' => 'unpaid',
            'billed_at' => now(),
        ]);
    }

    private function pay(Fee $fee, array $overrides = [])
    {
        return $this->actingAs($this->admin())->post(route('fees.payments.store', $fee), array_merge([
            'reference' => 'PAY-'.Str::upper(Str::random(8)),
            'amount' => '10.00',
            'paid_at' => '2026-11-12',
            'method' => 'cash',
            'note' => null,
        ], $overrides));
    }

    #[Test]
    public function an_admin_can_create_and_update_a_fee_structure(): void
    {
        $admin = $this->admin();
        $class = $this->class();

        $this->actingAs($admin)->post('/fees/structures', [
            'class_id' => $class->id,
            'name' => 'Tuition',
            'amount' => '125.50',
            'currency' => 'USD',
            'is_active' => '1',
        ])->assertRedirect(route('fees.structures.index'));

        $structure = FeeStructure::firstOrFail();
        $this->assertSame('125.50', $structure->amount);
        $this->assertTrue($structure->is_active);

        $this->actingAs($admin)->put(route('fees.structures.update', $structure), [
            'class_id' => $class->id,
            'name' => 'Tuition',
            'amount' => '130.00',
            'currency' => 'USD',
            'is_active' => '0',
        ])->assertRedirect(route('fees.structures.index'));

        $this->assertSame('130.00', $structure->fresh()->amount);
        $this->assertFalse($structure->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'fees.structure_created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'fees.structure_updated']);
    }

    #[Test]
    public function a_fee_structure_cannot_be_duplicated_for_the_same_class_and_currency(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $this->structure($class);

        $this->actingAs($admin)->post('/fees/structures', [
            'class_id' => $class->id,
            'name' => 'Tuition',
            'amount' => '99.00',
            'currency' => 'USD',
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, FeeStructure::count());
    }

    #[Test]
    public function billing_a_class_creates_one_fee_per_active_student_and_copies_details(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $otherClass = $this->class('Class 2');
        $structure = $this->structure($class, 'Tuition', '125.50');
        $first = $this->student($class, 'Amina');
        $second = $this->student($class, 'Hodan');
        $this->student($class, 'Left', 'left');
        $this->student($otherClass, 'Other');

        $this->actingAs($admin)->post('/fees/bill', [
            'fee_structure_id' => $structure->id,
            'due_date' => '2026-12-01',
        ])->assertRedirect(route('fees.index', ['class_id' => $class->id]));

        $this->assertSame(2, Fee::count());

        $fee = Fee::where('student_id', $first->id)->firstOrFail();
        $this->assertSame('125.50', $fee->amount);
        $this->assertSame('0.00', $fee->amount_paid);
        $this->assertSame('USD', $fee->currency);
        $this->assertSame($class->id, $fee->class_id);
        $this->assertSame($structure->id, $fee->fee_structure_id);
        $this->assertSame('unpaid', $fee->status);
        $this->assertSame('2026-12-01', $fee->due_date->toDateString());

        $first->update(['class_id' => $otherClass->id]);
        $this->assertSame($class->id, $fee->fresh()->class_id);
        $this->assertTrue($second->exists);
        $this->assertDatabaseHas('audit_logs', ['action' => 'fees.billed']);
    }

    #[Test]
    public function billing_the_same_class_twice_does_not_duplicate_fees(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $structure = $this->structure($class);
        $this->student($class);

        $this->actingAs($admin)->post('/fees/bill', ['fee_structure_id' => $structure->id])
            ->assertSessionHas('status');

        $this->actingAs($admin)->post('/fees/bill', ['fee_structure_id' => $structure->id])
            ->assertSessionHas('warning');

        $this->assertSame(1, Fee::count());
        $this->assertSame(0, FeePayment::count());
    }

    #[Test]
    public function an_inactive_structure_cannot_be_billed(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $structure = $this->structure($class, 'Tuition', '100.00', false);
        $this->student($class);

        $this->actingAs($admin)->post('/fees/bill', ['fee_structure_id' => $structure->id])
            ->assertSessionHasErrors('fee_structure_id');

        $this->assertSame(0, Fee::count());
    }

    #[Test]
    public function a_payment_updates_the_fee_and_creates_sequential_receipts(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $student = $this->student($class);
        $fee = $this->fee($student);

        $this->actingAs($admin)->post(route('fees.payments.store', $fee), [
            'reference' => 'PAY-001',
            'amount' => '40.00',
            'paid_at' => '2026-11-12',
            'method' => 'cash',
        ])->assertRedirect(route('fees.show', $fee));

        $fee->refresh();
        $this->assertSame('40.00', $fee->amount_paid);
        $this->assertSame('partial', $fee->status);
        $this->assertSame('RCPT-20261112-000001', FeePayment::firstOrFail()->receipt_number);

        $this->actingAs($admin)->post(route('fees.payments.store', $fee), [
            'reference' => 'PAY-002',
            'amount' => '60.00',
            'paid_at' => '2026-11-12',
            'method' => 'bank',
        ])->assertRedirect(route('fees.show', $fee));

        $fee->refresh();
        $this->assertSame('100.00', $fee->amount_paid);
        $this->assertSame('paid', $fee->status);
        $this->assertSame('RCPT-20261112-000002', FeePayment::orderByDesc('id')->firstOrFail()->receipt_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'fees.payment_recorded']);
    }

    #[Test]
    public function overpayment_and_duplicate_references_are_rejected(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $fee = $this->fee($this->student($class));

        $this->actingAs($admin)->post(route('fees.payments.store', $fee), [
            'reference' => 'PAY-DUP',
            'amount' => '80.00',
            'paid_at' => '2026-11-12',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('fees.payments.store', $fee), [
            'reference' => 'PAY-OVER',
            'amount' => '30.00',
            'paid_at' => '2026-11-12',
        ])->assertSessionHasErrors('amount');

        $this->actingAs($admin)->post(route('fees.payments.store', $fee), [
            'reference' => 'PAY-DUP',
            'amount' => '10.00',
            'paid_at' => '2026-11-12',
        ])->assertSessionHasErrors('reference');

        $this->assertSame(1, FeePayment::count());
        $this->assertSame('80.00', $fee->fresh()->amount_paid);
    }

    #[Test]
    public function a_stale_fee_model_cannot_overpay(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $fee = $this->fee($this->student($class), '100.00');
        $action = app(RecordFeePaymentAction::class);

        $action->handle($fee, [
            'reference' => 'PAY-STALE-1',
            'amount' => '20.00',
            'paid_at' => '2026-11-12',
        ], $admin);

        $this->expectException(ValidationException::class);

        $action->handle($fee, [
            'reference' => 'PAY-STALE-2',
            'amount' => '90.00',
            'paid_at' => '2026-11-12',
        ], $admin);
    }

    #[Test]
    public function a_void_fee_cannot_take_a_payment(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $fee = $this->fee($this->student($class));

        $this->actingAs($admin)->post(route('fees.void', $fee), ['reason' => 'Billed in error'])
            ->assertRedirect(route('fees.show', $fee));

        $this->actingAs($admin)->post(route('fees.payments.store', $fee), [
            'reference' => 'PAY-VOID',
            'amount' => '10.00',
            'paid_at' => '2026-11-12',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, FeePayment::count());
    }

    #[Test]
    public function reversing_a_payment_restores_the_balance_and_cannot_be_done_twice(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $fee = $this->fee($this->student($class));

        $this->pay($fee, ['reference' => 'PAY-REVERSE', 'amount' => '100.00'])
            ->assertSessionHasNoErrors();

        $payment = FeePayment::firstOrFail();

        $this->actingAs($admin)->post(route('fees.payments.reverse', [$fee, $payment]), [
            'reason' => 'Bank transfer returned',
        ])->assertRedirect(route('fees.show', $fee));

        $fee->refresh();
        $this->assertSame('0.00', $fee->amount_paid);
        $this->assertSame('unpaid', $fee->status);

        $reversal = FeePayment::where('type', 'reversal')->firstOrFail();
        $this->assertSame($payment->id, $reversal->reversal_of_id);
        $this->assertSame('100.00', $reversal->amount);
        $this->assertSame('Bank transfer returned', $reversal->reason);
        $this->assertSame('REV-PAY-REVERSE', $reversal->reference);
        $this->assertMatchesRegularExpression('/^RCPT-\d{8}-\d{6}$/', $reversal->receipt_number);

        $this->actingAs($admin)->get(route('fees.payments.receipt', $reversal))
            ->assertSuccessful()
            ->assertSee('Reversal')
            ->assertSee($payment->receipt_number);

        $this->actingAs($admin)->post(route('fees.payments.reverse', [$fee, $payment]), [
            'reason' => 'Again',
        ])->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post(route('fees.payments.reverse', [$fee, $reversal]), [
            'reason' => 'Reverse the reversal',
        ])->assertSessionHasErrors('reason');

        $this->assertSame(1, FeePayment::where('type', 'reversal')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'fees.payment_reversed']);
    }

    #[Test]
    public function voiding_requires_no_payments_and_a_reason(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $fee = $this->fee($this->student($class));

        $this->actingAs($admin)->post(route('fees.void', $fee), [])
            ->assertSessionHasErrors('reason');

        $this->pay($fee, ['reference' => 'PAY-VOID-BLOCK', 'amount' => '10.00'])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('fees.void', $fee), ['reason' => 'No longer needed'])
            ->assertSessionHasErrors('reason');

        $fee->refresh();
        $this->assertSame('partial', $fee->status);

        $this->actingAs($admin)->post(route('fees.payments.reverse', [$fee, FeePayment::firstOrFail()]), [
            'reason' => 'Refunded',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('fees.void', $fee), ['reason' => 'No longer needed'])
            ->assertRedirect(route('fees.show', $fee));

        $fee->refresh();
        $this->assertSame('void', $fee->status);
        $this->assertSame('No longer needed', $fee->void_reason);
        $this->assertNotNull($fee->voided_at);
        $this->assertSame('0.00', FeeBalance::balance($fee));
        $this->assertDatabaseHas('audit_logs', ['action' => 'fees.voided']);
    }

    #[Test]
    public function the_receipt_is_available_to_admins_and_hidden_from_teachers(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $fee = $this->fee($this->student($class));

        $this->pay($fee, ['reference' => 'PAY-RECEIPT', 'amount' => '25.00'])
            ->assertSessionHasNoErrors();

        $payment = FeePayment::firstOrFail();

        $this->actingAs($admin)->get(route('fees.payments.receipt', $payment))
            ->assertSuccessful()
            ->assertSee($payment->receipt_number)
            ->assertSee('Amina');

        $this->actingAs($this->teacher())->get(route('fees.payments.receipt', $payment))
            ->assertForbidden();
    }

    #[Test]
    public function the_billing_preview_counts_only_active_students_in_the_class(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $structure = $this->structure($class);
        $activeBilled = $this->student($class, 'Amina');
        $activeUnbilled = $this->student($class, 'Hodan');
        $leftBilled = $this->student($class, 'Left', 'left');

        $this->fee($activeBilled, '100.00', $structure);
        $this->fee($leftBilled, '100.00', $structure);

        $response = $this->actingAs($admin)->get('/fees/bill')->assertSuccessful();

        $preview = $response->viewData('structures')->firstWhere('id', $structure->id);

        $this->assertSame(2, $preview->active_students_count);
        $this->assertSame(1, $preview->billed_count);
        $this->assertTrue($activeUnbilled->exists);
    }

    #[Test]
    public function the_fee_pages_render_for_an_admin(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $structure = $this->structure($class);
        $fee = $this->fee($this->student($class), '100.00', $structure);

        foreach ([
            '/fees',
            '/fees/structures',
            '/fees/structures/create',
            '/fees/structures/'.$structure->id.'/edit',
            '/fees/bill',
            '/fees/'.$fee->id,
        ] as $uri) {
            $this->actingAs($admin)->get($uri)->assertSuccessful();
        }
    }

    #[Test]
    public function teachers_cannot_open_or_change_fees(): void
    {
        $teacher = $this->teacher();
        $class = $this->class();
        $fee = $this->fee($this->student($class));

        $this->actingAs($teacher)->get('/fees')->assertForbidden();
        $this->actingAs($teacher)->get('/fees/structures')->assertForbidden();
        $this->actingAs($teacher)->get('/fees/bill')->assertForbidden();
        $this->actingAs($teacher)->post(route('fees.payments.store', $fee), [
            'reference' => 'PAY-TEACHER',
            'amount' => '10.00',
            'paid_at' => '2026-11-12',
        ])->assertForbidden();
    }

    #[Test]
    public function the_fees_list_can_be_searched_and_filtered(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $paidStudent = $this->student($class, 'Amina');
        $unpaidStudent = $this->student($class, 'Hodan');
        $paid = $this->fee($paidStudent, '100.00');
        $unpaid = $this->fee($unpaidStudent, '60.00');

        $paid->update(['amount_paid' => '100.00', 'status' => 'paid']);

        $this->actingAs($admin)->get('/fees?search=Amina&status=paid')
            ->assertSuccessful()
            ->assertSee('Amina')
            ->assertDontSee('Hodan');

        $this->actingAs($admin)->get('/fees?class_id='.$class->id.'&status=unpaid')
            ->assertSuccessful()
            ->assertSee('Hodan')
            ->assertDontSee('Amina');

        $this->assertTrue($unpaid->exists);
    }

    #[Test]
    public function the_reconcile_command_repairs_corrupted_balances(): void
    {
        $admin = $this->admin();
        $class = $this->class();
        $fee = $this->fee($this->student($class));

        $this->pay($fee, ['reference' => 'PAY-RECONCILE', 'amount' => '40.00'])
            ->assertSessionHasNoErrors();

        Fee::whereKey($fee->id)->update(['amount_paid' => '0.00', 'status' => 'unpaid']);

        $this->assertSame('0.00', $fee->fresh()->amount_paid);

        $this->artisan('app:fees-reconcile', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('0.00', $fee->fresh()->amount_paid);

        $this->artisan('app:fees-reconcile')->assertSuccessful();

        $fee->refresh();
        $this->assertSame('40.00', $fee->amount_paid);
        $this->assertSame('partial', $fee->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'fees.reconciled']);
    }
}
