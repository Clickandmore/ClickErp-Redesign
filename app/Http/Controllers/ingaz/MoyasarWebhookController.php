<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\RentalContractInfo;
use App\Models\SponsorshipTransfer;
use App\Services\{MoyasarService, MoyasarContractPaymentService, MoyasarNotificationService, SponsorshipTransferMoyasarService};
use Illuminate\Http\Request;

class MoyasarWebhookController extends Controller
{
    public function callback(Request $request, MoyasarService $gateway, MoyasarContractPaymentService $contracts)
    {
        // إشعار الفاتورة من ميسر (callback_url) يصل POST؛ رجوع العميل (success_url) يصل GET.
        if ($request->isMethod('post')) {
            return $this->invoiceNotification($request, $contracts);
        }

        $id = $request->query('id');
        $contractId = (int)$request->query('contract_id');
        if (!is_string($id) || !$contractId) return $this->callbackView('error','رابط الدفع غير صحيح.');
        try {
            $transaction = $gateway->getPayment($id);
            $payment = $contracts->resolve($transaction['invoice_id'] ?? $id);
            if (!$payment || (int)$payment->rental_contract_id !== $contractId) return $this->callbackView('error','لم يتم العثور على بيانات الدفع.');
            $payment = $contracts->synchronize($payment);
            if ((float)$payment->refunded_amount > 0) return $this->callbackView('refunded','تم استرداد '.number_format((float)$payment->refunded_amount,2).' ريال من الدفعة.');
            if ($payment->paid_at) {
                if ($payment->reminder_id) {
                    $reminder = \App\Models\RentalContractExpiryReminder::find($payment->reminder_id);
                    if (!$reminder?->extension_id) return $this->callbackView('success','تم استلام الدفعة. سيتابع الموظف المختص مراجعة التمديد؛ لا يلزم الدفع مرة أخرى.');
                    return $this->callbackView('success','تم الدفع وتسجيل تمديد عقد الإيجار بنجاح. شكرًا لك!');
                }
                return $this->callbackView('success','تم الدفع بنجاح. شكرًا لك!');
            }
            $status = $payment->reminder_id
                ? \App\Models\RentalContractExpiryReminder::whereKey($payment->reminder_id)->value('payment_status')
                : RentalContractInfo::where('rental_contract_id',$contractId)
                ->where('moyasar_payment_id',$payment->invoice_id)->value('moyasar_payment_status');
            if (in_array($status,['failed','expired'],true)) return $this->callbackView('failed','لم تكتمل عملية الدفع.');
            return $this->callbackView('pending','لم يتأكد السداد بعد.');
        } catch (\Throwable $e) {
            report($e);
            return $this->callbackView('pending','تعذر التحقق من الدفع حاليًا. ستتم إعادة المزامنة عند وصول إشعار ميسر.');
        }
    }

    /** جسم الإشعار مجرد تنبيه: المرجع يُتحقق منه من ميسر ويجب أن يخص العقد نفسه. */
    private function invoiceNotification(Request $request, MoyasarContractPaymentService $contracts)
    {
        $contractId = (int) $request->query('contract_id');
        $reference = $request->input('id');
        if (!is_string($reference) || $reference === '') {
            $reference = RentalContractInfo::where('rental_contract_id', $contractId)->value('moyasar_payment_id');
        }
        if (!$contractId || !is_string($reference) || $reference === '') {
            return response()->json(['received' => false], 404);
        }
        try {
            $payment = $contracts->resolve($reference);
            if (!$payment || (int) $payment->rental_contract_id !== $contractId) {
                return response()->json(['received' => false], 404);
            }
            $contracts->synchronize($payment);

            return response()->json(['received' => true]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['received' => false], 503);
        }
    }

    /** رجوع العميل وإشعار الفاتورة لطلب نقل الخدمات؛ الرمز العشوائي يحدد الطلب ولا يُقبل السداد إلا من ميسر. */
    public function transferCallback(Request $request, string $token, SponsorshipTransferMoyasarService $payments)
    {
        $transfer = SponsorshipTransfer::withTrashed()->where('moyasar_callback_token', $token)->first();
        if (!$transfer) {
            return $request->isMethod('post')
                ? response()->json(['received' => false], 404)
                : $this->callbackView('error', 'رابط الدفع غير صحيح.');
        }
        try {
            $transfer = $payments->synchronize($transfer);
            if ($request->isMethod('post')) return response()->json(['received' => true]);
            if ((float) $transfer->moyasar_refunded_amount > 0) {
                return $this->callbackView('refunded', 'تم استرداد ' . number_format((float) $transfer->moyasar_refunded_amount, 2) . ' ريال من الدفعة.');
            }
            if ($transfer->moyasar_paid_at) return $this->callbackView('success', 'تم تأكيد سداد طلب نقل الخدمات. شكرًا لك!');
            if (in_array($transfer->moyasar_payment_status, ['failed', 'expired', 'canceled'], true)) {
                return $this->callbackView('failed', 'لم تكتمل عملية الدفع.');
            }
            return $this->callbackView('pending', 'لم يتأكد السداد بعد.');
        } catch (\Throwable $e) {
            report($e);
            if ($request->isMethod('post')) return response()->json(['received' => false], 503);
            return $this->callbackView('pending', 'تعذر التحقق من الدفع حاليًا. سيتم تحديث الطلب عند وصول تأكيد ميسر.');
        }
    }

    public function webhook(Request $request, MoyasarService $gateway, MoyasarNotificationService $notifications)
    {
        if (!$gateway->verifyWebhookSignature($request->getContent(),(string)$request->header('X-Moyasar-Signature',''))) {
            return response()->json(['error'=>'Invalid signature'],403);
        }
        try {
            $notifications->handle((array)$request->input('data',[]));
            return response()->json(['ok'=>true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok'=>false],503);
        }
    }

    private function callbackView(string $status, string $message)
    {
        return view('moyasar.callback',compact('status','message'));
    }
}
