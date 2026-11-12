<?php

namespace App\Actions\Fees;

use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Support\Audit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillClassFeesAction
{
    public function handle(FeeStructure $structure, ?Carbon $dueDate = null): array
    {
        return DB::transaction(function () use ($structure, $dueDate) {
            $structure = FeeStructure::whereKey($structure->id)->lockForUpdate()->firstOrFail();

            if (! $structure->is_active) {
                throw ValidationException::withMessages([
                    'fee_structure_id' => __('messages.fee_structure_inactive'),
                ]);
            }

            $students = Student::where('class_id', $structure->class_id)
                ->where('status', 'active')
                ->orderBy('id')
                ->get();

            $created = 0;
            $skipped = 0;

            foreach ($students as $student) {
                $fee = Fee::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'fee_structure_id' => $structure->id,
                    ],
                    [
                        'class_id' => $structure->class_id,
                        'title' => $structure->name,
                        'amount' => $structure->amount,
                        'amount_paid' => '0.00',
                        'currency' => $structure->currency,
                        'status' => 'unpaid',
                        'due_date' => $dueDate?->toDateString(),
                        'billed_at' => now(),
                    ]
                );

                if ($fee->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }

            if ($created > 0) {
                Audit::record('fees.billed', 'Class billed for a fee structure.', [
                    'fee_structure_id' => $structure->id,
                    'class_id' => $structure->class_id,
                    'created' => $created,
                    'skipped' => $skipped,
                ]);
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
                'students' => $students->count(),
            ];
        }, 3);
    }
}
