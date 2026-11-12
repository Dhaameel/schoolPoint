<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fees\BillClassFeesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BillClassFeesRequest;
use App\Http\Requests\Admin\FeeStructureRequest;
use App\Models\Fee;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Support\Audit;
use App\Support\FeeBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Fee::class);

        $search = trim((string) $request->query('search', ''));
        $classId = $request->query('class_id', 'all');
        $status = (string) $request->query('status', 'all');

        if (! in_array($status, ['all', 'unpaid', 'partial', 'paid', 'void'], true)) {
            $status = 'all';
        }

        $fees = Fee::query()
            ->with(['student', 'schoolClass'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('student_id', 'like', "%{$search}%");
                        })
                        ->orWhereHas('payments', function ($query) use ($search) {
                            $query->where('reference', 'like', "%{$search}%")
                                ->orWhere('receipt_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($classId !== 'all', fn ($query) => $query->where('class_id', $classId))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.fees.index', compact('fees', 'classes', 'search', 'classId', 'status'));
    }

    public function show(Fee $fee)
    {
        $this->authorize('view', $fee);

        $fee->load([
            'student',
            'schoolClass',
            'structure',
            'payments.createdBy',
            'payments.reversalOf',
            'payments.reversedBy',
        ]);

        $netPaid = FeeBalance::netPaid($fee);
        $balance = $fee->status === 'void' ? '0.00' : bcsub((string) $fee->amount, $netPaid, 2);

        return view('admin.fees.show', compact('fee', 'balance', 'netPaid'));
    }

    public function structures(Request $request)
    {
        $this->authorize('viewAny', FeeStructure::class);

        $search = trim((string) $request->query('search', ''));
        $state = (string) $request->query('state', 'all');

        if (! in_array($state, ['all', 'active', 'inactive'], true)) {
            $state = 'all';
        }

        $structures = FeeStructure::query()
            ->with('schoolClass')
            ->withCount('fees')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('schoolClass', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($state === 'active', fn ($query) => $query->where('is_active', true))
            ->when($state === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.fees.structures', compact('structures', 'search', 'state'));
    }

    public function createStructure()
    {
        $this->authorize('create', FeeStructure::class);

        $classes = SchoolClass::orderBy('name')->get();
        $defaultCurrency = Setting::get('currency', 'USD');

        return view('admin.fees.structure-create', compact('classes', 'defaultCurrency'));
    }

    public function storeStructure(FeeStructureRequest $request)
    {
        $this->authorize('create', FeeStructure::class);

        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $structure = FeeStructure::create($data);

        Audit::record('fees.structure_created', 'Fee structure created.', [
            'fee_structure_id' => $structure->id,
            'class_id' => $structure->class_id,
            'amount' => $structure->amount,
            'currency' => $structure->currency,
        ]);

        return redirect()->route('fees.structures.index')->with('status', __('messages.fee_structure_created'));
    }

    public function editStructure(FeeStructure $structure)
    {
        $this->authorize('update', $structure);

        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.fees.structure-edit', compact('structure', 'classes'));
    }

    public function updateStructure(FeeStructureRequest $request, FeeStructure $structure)
    {
        $this->authorize('update', $structure);

        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $structure->update($data);

        Audit::record('fees.structure_updated', 'Fee structure updated.', [
            'fee_structure_id' => $structure->id,
            'class_id' => $structure->class_id,
            'amount' => $structure->amount,
            'currency' => $structure->currency,
            'is_active' => $structure->is_active,
        ]);

        return redirect()->route('fees.structures.index')->with('status', __('messages.fee_structure_updated'));
    }

    public function billForm(Request $request)
    {
        $this->authorize('bill', Fee::class);

        $structures = FeeStructure::query()
            ->with('schoolClass')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $classIds = $structures->pluck('class_id')->unique()->values();

        $activeCounts = Student::query()
            ->whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->selectRaw('class_id, count(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        $billedCounts = Fee::query()
            ->whereIn('fee_structure_id', $structures->pluck('id'))
            ->whereHas('student', function ($query) {
                $query->where('status', 'active')
                    ->whereColumn('students.class_id', 'fees.class_id');
            })
            ->selectRaw('fee_structure_id, count(*) as total')
            ->groupBy('fee_structure_id')
            ->pluck('total', 'fee_structure_id');

        $structures->each(function (FeeStructure $structure) use ($activeCounts, $billedCounts) {
            $structure->setAttribute('active_students_count', (int) ($activeCounts[$structure->class_id] ?? 0));
            $structure->setAttribute('billed_count', (int) ($billedCounts[$structure->id] ?? 0));
        });

        $selectedStructureId = $request->query('structure_id');

        return view('admin.fees.bill', compact('structures', 'selectedStructureId'));
    }

    public function bill(BillClassFeesRequest $request, BillClassFeesAction $action)
    {
        $this->authorize('bill', Fee::class);

        $data = $request->validated();
        $structure = FeeStructure::findOrFail($data['fee_structure_id']);
        $dueDate = ! empty($data['due_date']) ? Carbon::parse($data['due_date']) : null;

        $result = $action->handle($structure, $dueDate);

        if ($result['created'] === 0) {
            $message = $result['skipped'] > 0
                ? __('messages.bill_none_created', ['count' => $result['skipped']])
                : __('messages.bill_no_students');

            return redirect()->route('fees.index', ['class_id' => $structure->class_id])->with('warning', $message);
        }

        $redirect = redirect()
            ->route('fees.index', ['class_id' => $structure->class_id])
            ->with('status', __('messages.bill_saved', ['count' => $result['created']]));

        if ($result['skipped'] > 0) {
            $redirect->with('warning', __('messages.bill_skipped', ['count' => $result['skipped']]));
        }

        return $redirect;
    }
}
