<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Students\CreateStudentAction;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentImportController extends Controller
{
    private const MAX_ROWS = 500;

    private const HEADERS = [
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'guardian_name',
        'guardian_phone',
        'admission_number',
        'class_name',
        'status',
    ];

    public function index()
    {
        $this->authorize('create', Student::class);

        return view('admin.students.import');
    }

    public function template()
    {
        $this->authorize('create', Student::class);

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::HEADERS);
            fputcsv($handle, [
                'Amina', 'Yusuf', 'Female', '2015-04-12', 'Hodan Yusuf', '0612345678',
                'ADM-001', 'Class 1', 'active',
            ]);
            fclose($handle);
        }, 'student-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function preview(Request $request)
    {
        $this->authorize('create', Student::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle, 0, ',', '"', '');

        if ($header) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }

        if (! $header || array_map('trim', $header) !== self::HEADERS) {
            fclose($handle);

            return back()->with('error', __('messages.import_bad_headers'));
        }

        $classes = SchoolClass::all()->keyBy(fn ($class) => strtolower($class->name));
        $existingAdmissions = Student::whereNotNull('admission_number')->pluck('admission_number')->map(fn ($value) => strtolower($value))->all();
        $seenAdmissions = [];
        $rows = [];
        $line = 1;

        while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;

            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $values = array_pad(array_map(fn ($value) => trim((string) $value), $values), count(self::HEADERS), '');
            $data = array_combine(self::HEADERS, $values);
            $errors = [];

            foreach (['first_name', 'last_name', 'guardian_name', 'guardian_phone', 'class_name'] as $required) {
                if ($data[$required] === '') {
                    $errors[] = __('messages.import_required', ['field' => $required]);
                }
            }

            $class = $classes->get(strtolower($data['class_name']));

            if ($data['class_name'] !== '' && ! $class) {
                $errors[] = __('messages.import_unknown_class', ['class' => $data['class_name']]);
            }

            if ($data['date_of_birth'] !== '' && ! strtotime($data['date_of_birth'])) {
                $errors[] = __('messages.import_bad_date');
            }

            $status = $data['status'] !== '' ? $data['status'] : 'active';

            if (! in_array($status, ['active', 'graduated', 'left'], true)) {
                $errors[] = __('messages.import_bad_status');
            }

            $admission = $data['admission_number'];

            if ($admission !== '') {
                $key = strtolower($admission);

                if (in_array($key, $existingAdmissions, true) || in_array($key, $seenAdmissions, true)) {
                    $errors[] = __('messages.import_duplicate_admission', ['admission' => $admission]);
                }

                $seenAdmissions[] = $key;
            }

            $rows[] = [
                'row' => $line,
                'data' => [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'gender' => $data['gender'] ?: null,
                    'date_of_birth' => $data['date_of_birth'] ?: null,
                    'guardian_name' => $data['guardian_name'],
                    'guardian_phone' => $data['guardian_phone'],
                    'admission_number' => $admission ?: null,
                    'class_id' => $class?->id,
                    'status' => $status,
                ],
                'class_name' => $data['class_name'],
                'errors' => $errors,
            ];
        }

        fclose($handle);

        if (count($rows) > self::MAX_ROWS) {
            return back()->with('error', __('messages.import_too_many_rows'));
        }

        $request->session()->put('student_import_rows', $rows);
        $invalidCount = collect($rows)->filter(fn ($row) => count($row['errors']) > 0)->count();

        return view('admin.students.import-preview', [
            'rows' => $rows,
            'invalidCount' => $invalidCount,
            'validCount' => count($rows) - $invalidCount,
        ]);
    }

    public function import(Request $request, CreateStudentAction $action)
    {
        $this->authorize('create', Student::class);

        $rows = $request->session()->get('student_import_rows', []);

        if (count($rows) === 0) {
            return redirect()->route('students.import')->with('error', __('messages.import_no_rows'));
        }

        $invalidCount = collect($rows)->filter(fn ($row) => count($row['errors']) > 0)->count();
        $skipBadRows = $request->boolean('skip_bad_rows');

        if ($invalidCount > 0 && ! $skipBadRows) {
            return back()->with('error', __('messages.import_has_errors'));
        }

        $toSave = $skipBadRows
            ? collect($rows)->filter(fn ($row) => count($row['errors']) === 0)->values()
            : collect($rows);

        if ($toSave->isEmpty()) {
            return back()->with('error', __('messages.import_no_valid_rows'));
        }

        DB::transaction(function () use ($toSave, $action) {
            foreach ($toSave as $row) {
                $action->handle($row['data']);
            }
        });

        Audit::record('students.imported', 'Students imported from CSV.', ['count' => $toSave->count()]);

        $request->session()->forget(['student_import_rows']);

        return redirect()->route('students.index')->with('status', __('messages.import_complete', ['count' => $toSave->count()]));
    }
}
