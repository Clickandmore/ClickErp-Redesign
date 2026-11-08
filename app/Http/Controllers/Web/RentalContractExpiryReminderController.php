<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\MoyasarService;
use App\Services\RentalContractCustomerActionService;
use Illuminate\Http\Request;

class RentalContractExpiryReminderController extends Controller
{
    public function show(string $token, RentalContractCustomerActionService $actions)
    {
        $reminder = $actions->resolve($token);
        $contract = $reminder->rentalContract()->with(['user', 'maid', 'info'])->firstOrFail();
        $canRespond = $actions->available($contract, $reminder);
        $options = !$reminder->response && $canRespond ? $actions->options($contract, $reminder) : [];
        $paymentMethods = \App\Support\NotificationAlertSettings::renewalPaymentMethods();
        return view('site.pages.rental_contract_expiry_reminder', compact('reminder', 'contract', 'canRespond', 'options', 'paymentMethods'));
    }

    public function respond(Request $request, string $token, RentalContractCustomerActionService $actions, MoyasarService $moyasar)
    {
        $data = $request->validate([
            'response' => ['required', 'in:extend,return'],
            'quote_token' => ['nullable', 'string', 'max:64'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'preferred_return_date' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addYear()->toDateString()],
            'response_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $reminder = $actions->choose($actions->resolve($token), $data);
        if ($data['response'] === 'return') {
            return redirect()->route('rental-contract-reminders.show', $token)
                ->with('success', 'تم تسجيل طلب تسليم العاملة. سيتواصل معكم الموظف لترتيب الاستلام وإكمال الإجراءات.');
        }
        if ($reminder->customer_payment_method && $reminder->customer_payment_method !== 'moyasar') {
            return redirect()->route('rental-contract-reminders.show', $token)
                ->with('success', 'تم تسجيل طلب التمديد وطريقة الدفع. سيتواصل معكم الموظف لتأكيد السداد وتنفيذ التمديد.');
        }
        try { $url = $actions->paymentUrl($reminder, $moyasar); }
        catch (\Throwable $e) { report($e); $url = null; }
        if ($url) return redirect()->away($url);
        return redirect()->route('rental-contract-reminders.show', $token)
            ->with('warning', 'تم تسجيل رغبتكم بالتمديد، ولم يسجل تمديد حتى الآن. تعذر تجهيز الدفع؛ سيتابع الموظف المختص الطلب.');
    }
}
