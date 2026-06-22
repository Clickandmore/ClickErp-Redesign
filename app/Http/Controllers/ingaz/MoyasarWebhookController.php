<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\RentalContractInfo;
use App\Services\ingaz\RentalContractFinanceService;
use App\Services\MoyasarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoyasarWebhookController extends Controller
{
    /**
     * صفحة نتيجة الدفع — يعود إليها العميل بعد إتمام الدفع أو فشله
     * لا تثق بالـ query string: تحقق دائماً من API ميسر مباشرةً
     */
    public function callback(Request $request, MoyasarService $moyasar, RentalContractFinanceService $financeService)
    {
        $contractId = $request->query('contract_id');
        $paymentId  = $request->query('id'); // ميسر تُرسل معرّف الدفعة في الـ callback

        if (!$contractId || !$paymentId) {
            Log::warning('Moyasar callback: missing contract_id or payment_id', $request->query());
            return $this->callbackView('error', 'رابط الدفع غير صحيح.');
        }

        $info = $this->findInfoForMoyasarReference((string) $paymentId, (int) $contractId);

        if (!$info) {
            Log::warning('Moyasar callback: no matching info record', [
                'contract_id' => $contractId,
                'payment_id'  => $paymentId,
            ]);
            return $this->callbackView('error', 'لم يتم العثور على بيانات الدفع.');
        }

        // التحقق من API ميسر مباشرةً
        $status = $moyasar->verifyPaymentStatus($paymentId);

        Log::info('Moyasar callback received', [
            'contract_id' => $contractId,
            'payment_id'  => $paymentId,
            'status'      => $status,
        ]);

        if ($status === 'paid') {
            $this->confirmMoyasarPayment($info, null, $financeService);

            return $this->callbackView('success', 'تم الدفع بنجاح. شكراً لك!');
        }

        if (in_array($status, ['failed', 'expired'], true)) {
            $info->update(['moyasar_payment_status' => $status]);
            return $this->callbackView('failed', 'لم يتم الدفع. يمكنك المحاولة مجدداً عبر الرابط الذي أُرسل لك.');
        }

        return $this->callbackView('pending', 'جارٍ التحقق من حالة الدفع...');
    }

    /**
     * Webhook من ميسر — يُرسل تلقائياً عند كل تغيير في حالة الدفع
     */
    public function webhook(Request $request, MoyasarService $moyasar, RentalContractFinanceService $financeService)
    {
        $rawBody  = $request->getContent();
        $signature = $request->header('X-Moyasar-Signature', '');

        if (!$moyasar->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Moyasar webhook: invalid signature');
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data      = $request->json()->all();
        $eventType = $data['type'] ?? '';
        $payment   = $data['data'] ?? [];
        $paymentId = $payment['id'] ?? null;
        $invoiceId = $payment['invoice_id'] ?? data_get($payment, 'source.invoice_id');
        $contractId = data_get($payment, 'metadata.contract_id');
        $status    = $payment['status'] ?? null;

        Log::info('Moyasar webhook received', [
            'event_type' => $eventType,
            'payment_id' => $paymentId,
            'status'     => $status,
        ]);

        if (!$paymentId && !$invoiceId && !$contractId) {
            return response()->json(['ok' => true]);
        }

        $info = $this->findInfoForMoyasarReference($paymentId, $contractId ? (int) $contractId : null, $invoiceId);

        if (!$info) {
            return response()->json(['ok' => true]);
        }

        if ($status === 'paid') {
            $amountInRiyals = ($payment['amount'] ?? 0) / 100;

            $this->confirmMoyasarPayment($info, $amountInRiyals, $financeService);

            Log::info('Moyasar webhook: payment confirmed', [
                'payment_id'  => $paymentId,
                'contract_id' => $info->rental_contract_id,
                'amount'      => $amountInRiyals,
            ]);
        } elseif (in_array($status, ['failed', 'expired', 'refunded'], true)) {
            $info->update(['moyasar_payment_status' => $status]);
        }

        return response()->json(['ok' => true]);
    }

    private function findInfoForMoyasarReference(?string $paymentId, ?int $contractId = null, ?string $invoiceId = null): ?RentalContractInfo
    {
        $baseQuery = RentalContractInfo::query();

        if ($contractId) {
            $baseQuery->where('rental_contract_id', $contractId);
        }

        $references = array_values(array_filter([$paymentId, $invoiceId]));

        if ($references) {
            $matched = (clone $baseQuery)
                ->whereIn('moyasar_payment_id', $references)
                ->first();

            if ($matched) {
                return $matched;
            }
        }

        if ($contractId) {
            return RentalContractInfo::query()
                ->where('rental_contract_id', $contractId)
                ->whereNotNull('moyasar_payment_id')
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function confirmMoyasarPayment(
        RentalContractInfo $info,
        ?float $paidAmount,
        RentalContractFinanceService $financeService
    ): void {
        $contractValue = (float) $info->contract_value;
        $paidAmount = $paidAmount && $paidAmount > 0
            ? min($paidAmount, $contractValue)
            : $contractValue;
        $remainingAmount = max(0, $contractValue - $paidAmount);

        $info->update([
            'moyasar_payment_status' => 'paid',
            'moyasar_paid_at'        => now(),
            'payment_type'           => 'moyasar',
            'payment_status'         => $remainingAmount <= 0.01 ? 'paid' : 'partial',
            'paid_amount'            => $paidAmount,
            'remaining_amount'       => $remainingAmount,
            'payment_confirmed_at'   => now(),
        ]);

        try {
            $contract = $info->rentalContract()
                ->with(['user', 'maid.nationality', 'info', 'financeInvoice'])
                ->first();

            if ($contract) {
                $financeService->syncPaymentForContract($contract);
            }
        } catch (\Throwable $e) {
            Log::error('Moyasar: payment confirmed but finance invoice sync failed', [
                'rental_contract_id' => $info->rental_contract_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function callbackView(string $status, string $message)
    {
        return view('moyasar.callback', compact('status', 'message'));
    }
}
