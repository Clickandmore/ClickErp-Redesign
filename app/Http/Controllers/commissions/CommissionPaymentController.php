<?php

namespace App\Http\Controllers\commissions;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Services\commissions\CommissionPaymentService;
use App\Services\commissions\CommissionProfileService;
use Illuminate\Http\Request;

class CommissionPaymentController extends Controller
{
    public function __construct(
        private readonly CommissionPaymentService $paymentService,
        private readonly CommissionProfileService $profileService
    ) {}

    public function index(Admin $admin)
    {
        $payments = $this->paymentService->getPaymentsForMarketer($admin->id);
        $balance = $this->profileService->getMarketerBalance($admin->id);

        return view('commissions.payments.index', compact('admin', 'payments', 'balance'));
    }

    public function store(Request $request, Admin $admin)
    {
        $balance = $this->profileService->getMarketerBalance($admin->id);

        $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:' . $balance],
            'payment_date' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $this->paymentService->issuePayment(
            $admin->id,
            (float) $request->amount,
            $request->payment_date,
            $request->notes,
            auth('admin')->id()
        );

        return redirect()
            ->route('commissions.marketers.payments.index', $admin->id)
            ->with('success', 'تم إصدار سند الصرف بنجاح برقم #' . $payment->id);
    }

    public function print(Admin $admin, int $payment)
    {
        $payment = $this->paymentService->getPaymentsForMarketer($admin->id)
            ->where('id', $payment)->first();

        abort_if(! $payment, 404);

        return view('commissions.payments.print', compact('admin', 'payment'));
    }
}
