<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fees\RecordFeePaymentAction;
use App\Actions\Fees\ReverseFeePaymentAction;
use App\Actions\Fees\VoidFeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeePaymentRequest;
use App\Models\Fee;
use App\Models\FeePayment;
use Illuminate\Http\Request;

class FeePaymentController extends Controller
{
    public function store(FeePaymentRequest $request, Fee $fee, RecordFeePaymentAction $action)
    {
        $this->authorize('recordPayment', $fee);

        $payment = $action->handle($fee, $request->validated(), $request->user());

        return redirect()
            ->route('fees.show', $fee)
            ->with('status', __('messages.payment_saved', ['receipt' => $payment->receipt_number]));
    }

    public function reverse(Request $request, Fee $fee, FeePayment $payment, ReverseFeePaymentAction $action)
    {
        $this->authorize('reversePayment', $fee);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->handle($fee, $payment, $data['reason'], $request->user());

        return redirect()
            ->route('fees.show', $fee)
            ->with('status', __('messages.payment_reversed'));
    }

    public function void(Request $request, Fee $fee, VoidFeeAction $action)
    {
        $this->authorize('void', $fee);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->handle($fee, $data['reason']);

        return redirect()
            ->route('fees.show', $fee)
            ->with('status', __('messages.fee_voided'));
    }

    public function receipt(FeePayment $payment)
    {
        $payment->load(['fee.student', 'fee.schoolClass', 'createdBy', 'reversalOf']);

        $this->authorize('view', $payment->fee);

        return view('admin.fees.receipt', compact('payment'));
    }
}
