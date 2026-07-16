<?php

namespace App\Services;

use App\Exceptions\RentalOrderPaymentException;
use App\Models\ingaz\Order;
use App\Models\RentalOrderPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RentalOrderPaymentService
{
    private MoyasarService $moyasar;

    public function __construct(MoyasarService $moyasar)
    {
        $this->moyasar = $moyasar;
    }

    public function initiate(Order $order): RentalOrderPayment
    {
        $order->loadMissing('details.package');

        if ((int) $order->job_type_id !== 2) {
            throw new RentalOrderPaymentException('هذا الطلب ليس طلب إيجار.');
        }

        $package = $order->details?->package;
        $amount = round((float) ($package?->price_with_tax ?? 0), 2);

        if (!$package || $amount <= 0) {
            throw new RentalOrderPaymentException('لا توجد باقة صالحة للدفع مرتبطة بالطلب.');
        }

        $existingPayment = RentalOrderPayment::query()
            ->where('order_id', $order->id)
            ->where('provider', RentalOrderPayment::PROVIDER_MOYASAR)
            ->where('status', RentalOrderPayment::STATUS_PENDING)
            ->whereNotNull('payment_url')
            ->latest('id')
            ->first();

        if ($existingPayment) {
            $existingPayment = $this->synchronize($existingPayment);

            if ($existingPayment->status === RentalOrderPayment::STATUS_PENDING) {
                return $existingPayment;
            }
        }

        $payment = DB::transaction(function () use ($order, $package, $amount) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            $paidPayment = RentalOrderPayment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('provider', RentalOrderPayment::PROVIDER_MOYASAR)
                ->where('status', RentalOrderPayment::STATUS_PAID)
                ->first();

            if ($paidPayment) {
                throw new RentalOrderPaymentException('تم دفع هذا الطلب مسبقاً.');
            }

            $reusablePayment = RentalOrderPayment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('provider', RentalOrderPayment::PROVIDER_MOYASAR)
                ->where('status', RentalOrderPayment::STATUS_PENDING)
                ->whereNotNull('payment_url')
                ->latest('id')
                ->first();

            if ($reusablePayment) {
                return $reusablePayment;
            }

            $creatingPayment = RentalOrderPayment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('provider', RentalOrderPayment::PROVIDER_MOYASAR)
                ->where('status', RentalOrderPayment::STATUS_CREATING)
                ->latest('id')
                ->first();

            if ($creatingPayment && $creatingPayment->created_at?->gt(now()->subMinutes(2))) {
                throw new RentalOrderPaymentException('جاري إنشاء رابط الدفع، يرجى المحاولة بعد لحظات.');
            }

            return RentalOrderPayment::query()->create([
                'order_id' => $lockedOrder->id,
                'user_id' => $lockedOrder->user_id,
                'package_id' => $package->id,
                'public_id' => (string) Str::uuid(),
                'provider' => RentalOrderPayment::PROVIDER_MOYASAR,
                'amount' => $amount,
                'currency' => 'SAR',
                'status' => RentalOrderPayment::STATUS_CREATING,
            ]);
        });

        if ($payment->status === RentalOrderPayment::STATUS_PENDING && $payment->payment_url) {
            return $payment;
        }

        $result = $this->moyasar->createPaymentLink(
            (float) $payment->amount,
            'Rental order #' . $order->order_code,
            route('api.app.moyasar.callback', ['payment' => $payment->public_id]),
            [
                'rental_order_payment_id' => $payment->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]
        );

        if (empty($result['id']) || empty($result['url'])) {
            $payment->update(['status' => RentalOrderPayment::STATUS_FAILED]);
            throw new RentalOrderPaymentException('تعذر إنشاء رابط الدفع من ميسر حالياً.');
        }

        $payment->update([
            'status' => RentalOrderPayment::STATUS_PENDING,
            'provider_invoice_id' => (string) $result['id'],
            'payment_url' => (string) $result['url'],
        ]);

        return $payment->fresh();
    }

    public function synchronize(RentalOrderPayment $payment, ?string $providerPaymentId = null): RentalOrderPayment
    {
        if ($payment->status === RentalOrderPayment::STATUS_PAID) {
            return $payment;
        }

        $reference = $providerPaymentId ?: $payment->provider_invoice_id;
        if (!$reference) {
            return $payment;
        }

        $payload = $providerPaymentId
            ? $this->moyasar->getPayment($providerPaymentId)
            : $this->moyasar->getInvoice($reference);

        if (!$payload) {
            return $payment;
        }

        return $this->applyProviderPayload($payment, $payload, $providerPaymentId);
    }

    public function applyProviderPayload(
        RentalOrderPayment $payment,
        array $payload,
        ?string $providerPaymentId = null
    ): RentalOrderPayment {
        $status = (string) ($payload['status'] ?? 'unknown');
        $invoiceId = (string) ($payload['invoice_id'] ?? data_get($payload, 'source.invoice_id', ''));
        $metadataPaymentId = (int) data_get($payload, 'metadata.rental_order_payment_id', 0);

        if ($invoiceId !== '' && !hash_equals((string) $payment->provider_invoice_id, $invoiceId)) {
            Log::warning('Moyasar rental order payment invoice mismatch', [
                'payment_id' => $payment->id,
                'expected_invoice_id' => $payment->provider_invoice_id,
                'received_invoice_id' => $invoiceId,
            ]);

            return $payment;
        }

        if ($metadataPaymentId && $metadataPaymentId !== (int) $payment->id) {
            return $payment;
        }

        if ($status === RentalOrderPayment::STATUS_PAID) {
            $receivedAmount = array_key_exists('amount', $payload)
                ? round(((float) $payload['amount']) / 100, 2)
                : null;
            $currency = strtoupper((string) ($payload['currency'] ?? $payment->currency));

            if (($receivedAmount !== null && abs($receivedAmount - (float) $payment->amount) > 0.001)
                || $currency !== $payment->currency) {
                Log::warning('Moyasar rental order payment amount mismatch', [
                    'payment_id' => $payment->id,
                    'expected_amount' => $payment->amount,
                    'received_amount' => $receivedAmount,
                    'currency' => $currency,
                ]);

                return $payment;
            }

            return $this->markPaid($payment, $payload, $providerPaymentId ?: ($payload['id'] ?? null));
        }

        if (in_array($status, [
            RentalOrderPayment::STATUS_FAILED,
            RentalOrderPayment::STATUS_EXPIRED,
            RentalOrderPayment::STATUS_REFUNDED,
        ], true)) {
            $payment->update([
                'status' => $status,
                'provider_payment_id' => $providerPaymentId ?: ($payload['id'] ?? $payment->provider_payment_id),
                'provider_payload' => $payload,
            ]);
        }

        return $payment->fresh();
    }

    private function markPaid(
        RentalOrderPayment $payment,
        array $payload,
        ?string $providerPaymentId
    ): RentalOrderPayment {
        DB::transaction(function () use ($payment, $payload, $providerPaymentId) {
            $lockedPayment = RentalOrderPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status !== RentalOrderPayment::STATUS_PAID) {
                $lockedPayment->update([
                    'status' => RentalOrderPayment::STATUS_PAID,
                    'provider_payment_id' => $providerPaymentId,
                    'provider_payload' => $payload,
                    'paid_at' => now(),
                ]);

                Order::query()
                    ->whereKey($lockedPayment->order_id)
                    ->update(['payment_method' => RentalOrderPayment::PROVIDER_MOYASAR]);
            }
        });

        return $payment->fresh();
    }
}
