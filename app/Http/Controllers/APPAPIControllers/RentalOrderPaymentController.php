<?php

namespace App\Http\Controllers\APPAPIControllers;

use App\Exceptions\RentalOrderPaymentException;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Order;
use App\Models\RentalOrderPayment;
use App\Services\MoyasarService;
use App\Services\RentalOrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RentalOrderPaymentController extends Controller
{
    public function store(
        Order $order,
        RentalOrderPaymentService $paymentService
    ) {
        $this->ensureOrderOwner($order);

        try {
            $payment = $paymentService->initiate($order);
        } catch (RentalOrderPaymentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Unable to initiate app rental order payment', [
                'order_id' => $order->id,
                'user_id' => auth('api')->id(),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'تعذر إنشاء رابط الدفع حالياً، يرجى المحاولة لاحقاً.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء رابط الدفع بنجاح.',
            'data' => $this->paymentData($payment),
        ], 201);
    }

    public function show(
        Order $order,
        RentalOrderPaymentService $paymentService
    ) {
        $this->ensureOrderOwner($order);

        $payment = RentalOrderPayment::query()
            ->where('order_id', $order->id)
            ->where('provider', RentalOrderPayment::PROVIDER_MOYASAR)
            ->latest('id')
            ->firstOrFail();

        try {
            // Never answer from the cached status: the customer may close Moyasar before callback.
            $payment = $paymentService->synchronize($payment);
        } catch (\Throwable $exception) {
            Log::warning('Unable to synchronize app rental order payment status', [
                'payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'تعذر التحقق من حالة الدفع من ميسر حالياً.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => $this->paymentData($payment),
        ]);
    }

    public function callback(
        Request $request,
        RentalOrderPaymentService $paymentService
    ) {
        $payment = RentalOrderPayment::query()
            ->where('public_id', (string) $request->query('payment'))
            ->where('provider', RentalOrderPayment::PROVIDER_MOYASAR)
            ->first();

        if (!$payment) {
            return $this->callbackView('error', 'رابط الدفع غير صحيح.');
        }

        try {
            $payment = $paymentService->synchronize($payment, $request->query('id'));
        } catch (\Throwable $exception) {
            Log::error('App rental order payment callback failed', [
                'payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);

            return $this->callbackView('error', 'تعذر التحقق من عملية الدفع حالياً.');
        }

        if ($payment->status === RentalOrderPayment::STATUS_PAID) {
            return $this->callbackView('success', 'تم الدفع بنجاح. شكراً لك!');
        }

        if (in_array($payment->status, [
            RentalOrderPayment::STATUS_FAILED,
            RentalOrderPayment::STATUS_EXPIRED,
            'voided',
            'canceled',
        ], true)) {
            return $this->callbackView('failed', 'لم تكتمل عملية الدفع، يمكنك المحاولة مجدداً من التطبيق.');
        }

        if (in_array($payment->status, ['refunded','partially_refunded'], true)) {
            return $this->callbackView($payment->status,'المبلغ المسترد: '.number_format((float)$payment->refunded_amount,2).' ريال.');
        }
        return $this->callbackView('pending', 'جاري التحقق من حالة الدفع...');
    }

    public function webhook(Request $request, MoyasarService $moyasar, RentalOrderPaymentService $paymentService)
    {
        return app(\App\Http\Controllers\ingaz\MoyasarWebhookController::class)->webhook(
            $request,$moyasar,app(\App\Services\MoyasarNotificationService::class));
    }
    private function ensureOrderOwner(Order $order): void
    {
        abort_unless((int) $order->user_id === (int) auth('api')->id(), 404);
    }

    private function paymentData(RentalOrderPayment $payment): array
    {
        return [
            'id' => (int) $payment->id,
            'public_id' => (string) $payment->public_id,
            'order_id' => (int) $payment->order_id,
            'status' => (string) $payment->status,
            'is_paid' => $payment->status === RentalOrderPayment::STATUS_PAID,
            'amount' => (float) $payment->amount,
            'refunded_amount' => (string) ($payment->refunded_amount ?? '0.00'),
            'net_paid_amount' => $payment->paid_at ? round((float)$payment->amount-(float)$payment->refunded_amount,2) : 0,
            'currency' => (string) $payment->currency,
            'payment_url' => $payment->payment_url,
            'paid_at' => optional($payment->paid_at)->toIso8601String(),
        ];
    }

    private function callbackView(string $status, string $message)
    {
        return view('moyasar.callback', compact('status', 'message'));
    }
}
