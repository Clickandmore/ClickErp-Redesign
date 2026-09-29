<?php

namespace App\Services;

use App\Models\RentalOrderPayment;
use App\Models\Hourly\HourlyOrderPayment;
use App\Services\Hourly\HourlyPaymentService;

class MoyasarNotificationService
{
    public function handle(array $payload): void
    {
        $reference = $payload['invoice_id'] ?? data_get($payload,'source.invoice_id');
        if (!is_string($reference) || $reference === '') {
            $id = $payload['id'] ?? null;
            if (!is_string($id) || $id === '') return;
            $transaction = app(MoyasarService::class)->getPayment($id);
            if (!$transaction) throw new \RuntimeException('Unable to verify Moyasar notification.');
            $reference = $transaction['invoice_id'] ?? $id;
        }
        $order = RentalOrderPayment::where('provider','moyasar')->where('provider_invoice_id',$reference)->first();
        if ($order) { app(RentalOrderPaymentService::class)->synchronize($order); return; }
        $hourly = HourlyOrderPayment::where('provider','moyasar')->where('provider_reference',$reference)->first();
        if ($hourly) { app(HourlyPaymentService::class)->synchronize($hourly); return; }
        $contracts = app(MoyasarContractPaymentService::class);
        $payment = $contracts->resolve($reference);
        if ($payment) $contracts->synchronize($payment);
    }
}
