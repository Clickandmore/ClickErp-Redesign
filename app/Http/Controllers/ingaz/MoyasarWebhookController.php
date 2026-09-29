<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Services\{MoyasarService, MoyasarContractPaymentService, MoyasarNotificationService};
use Illuminate\Http\Request;

class MoyasarWebhookController extends Controller
{
    public function callback(Request $request, MoyasarService $gateway, MoyasarContractPaymentService $contracts)
    {
        $id = $request->query('id');
        $contractId = (int)$request->query('contract_id');
        if (!is_string($id) || !$contractId) return $this->callbackView('error','رابط الدفع غير صحيح.');
        try {
            $transaction = $gateway->getPayment($id);
            $payment = $contracts->resolve($transaction['invoice_id'] ?? $id);
            if (!$payment || (int)$payment->rental_contract_id !== $contractId) return $this->callbackView('error','لم يتم العثور على بيانات الدفع.');
            $payment = $contracts->synchronize($payment);
            if ((float)$payment->refunded_amount > 0) return $this->callbackView('refunded','تم استرداد '.number_format((float)$payment->refunded_amount,2).' ريال من الدفعة.');
            if ($payment->paid_at) return $this->callbackView('success','تم الدفع بنجاح. شكرًا لك!');
            $status = \App\Models\RentalContractInfo::where('rental_contract_id',$contractId)
                ->where('moyasar_payment_id',$payment->invoice_id)->value('moyasar_payment_status');
            if (in_array($status,['failed','expired'],true)) return $this->callbackView('failed','لم تكتمل عملية الدفع.');
            return $this->callbackView('pending','لم يتأكد السداد بعد.');
        } catch (\Throwable $e) {
            report($e);
            return $this->callbackView('pending','تعذر التحقق من الدفع حاليًا. ستتم إعادة المزامنة عند وصول إشعار ميسر.');
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
