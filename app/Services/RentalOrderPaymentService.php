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
            ->where('payment_url', 'like', 'https://%')
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
                ->whereIn('status', [RentalOrderPayment::STATUS_PAID, RentalOrderPayment::STATUS_PARTIALLY_REFUNDED])
                ->first();

            if ($paidPayment) {
                throw new RentalOrderPaymentException('تم دفع هذا الطلب مسبقاً.');
            }

            $reusablePayment = RentalOrderPayment::query()
                ->where('order_id', $lockedOrder->id)
                ->where('provider', RentalOrderPayment::PROVIDER_MOYASAR)
                ->where('status', RentalOrderPayment::STATUS_PENDING)
                ->where('payment_url', 'like', 'https://%')
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
            $this->appUrl('api.app.moyasar.callback', ['payment' => $payment->public_id]),
            [
                'rental_order_payment_id' => $payment->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ]
        );

        if (empty($result['id']) || !$this->isHttpsUrl($result['url'] ?? null)) {
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
        if (!$payment->provider_invoice_id) return $payment;
        $invoice = $this->moyasar->getInvoice($payment->provider_invoice_id);
        if (!$invoice) throw new \RuntimeException('Unable to fetch Moyasar invoice.');
        return DB::transaction(function () use ($payment, $invoice) {
            $locked = RentalOrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            $snapshot = \App\Support\MoyasarPaymentSnapshot::fromInvoice($invoice,
                $locked->provider_invoice_id, (float) $locked->amount);
            if (!$snapshot) {
                if (!$locked->paid_at && in_array($invoice['status'] ?? '', ['failed', 'expired', 'voided', 'canceled'], true)) {
                    $locked->update(['status'=>$invoice['status'], 'provider_payload'=>$invoice]);
                }
                return $locked;
            }
            if ($snapshot['id'] && $locked->provider_payment_id
                && $locked->provider_payment_id !== $locked->provider_invoice_id
                && $locked->provider_payment_id !== $snapshot['id']) {
                throw new \RuntimeException('Moyasar payment reference mismatch.');
            }
            $refunded = max((float) $locked->refunded_amount, $snapshot['refunded']);
            $locked->update([
                'status'=>$refunded > 0 ? ($refunded >= (float) $locked->amount ? 'refunded' : 'partially_refunded') : 'paid',
                'refunded_amount'=>$refunded, 'provider_payload'=>$invoice,
                'provider_payment_id'=>$snapshot['id'] ?: $locked->provider_payment_id,
                'paid_at'=>$locked->paid_at ?: now(),
            ]);
            Order::query()->whereKey($locked->order_id)->update(['payment_method'=>RentalOrderPayment::PROVIDER_MOYASAR]);
            return $locked;
        });
    }

    public function applyProviderPayload(RentalOrderPayment $payment, array $payload, ?string $providerPaymentId = null): RentalOrderPayment
    {
        $invoiceId = (string) ($payload['invoice_id'] ?? data_get($payload, 'source.invoice_id', ''));
        if ($invoiceId !== '' && $invoiceId !== $payment->provider_invoice_id) return $payment;
        if (isset($payload['amount']) && (float)$payload['amount'] !== (float)round($payment->amount*100)) return $payment;
        if (isset($payload['currency']) && $payload['currency'] !== $payment->currency) return $payment;
        // Notifications are hints, including delayed or duplicated refund events.
        return $this->synchronize($payment);
    }

    private function appUrl(string $route, array $parameters = []): string
    {
        $base = rtrim((string) config('services.moyasar.app_callback_base_url'), '/');

        return $base.route($route, $parameters, false);
    }

    private function isHttpsUrl(mixed $url): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }
}
