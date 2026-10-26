<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RentalContractExpiryReminder;
use App\Services\MoyasarService;
use App\Services\RentalContractExpiryReminderService;
use App\Support\MoyasarSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RentalContractExpiryReminderController extends Controller
{
    public function show(string $token)
    {
        $reminder = $this->findReminder($token);
        $contract = $reminder->rentalContract()->with(['user', 'maid', 'info'])->firstOrFail();

        return view('site.pages.rental_contract_expiry_reminder', [
            'reminder' => $reminder,
            'contract' => $contract,
        ]);
    }

    public function respond(
        Request $request,
        string $token,
        RentalContractExpiryReminderService $reminderService,
        MoyasarService $moyasar
    ) {
        $data = $request->validate([
            'response' => ['required', 'in:extend,return'],
        ]);

        $reminder = $this->findReminder($token);
        $contract = $reminder->rentalContract()->with(['user', 'info'])->firstOrFail();

        $reminderService->markCustomerResponse($reminder, $data['response']);

        if ($data['response'] === 'return') {
            try {
                $reminderService->applyReturnDecision($reminder->fresh());
            } catch (\Throwable $exception) {
                Log::error('Unable to apply rental contract return decision from expiry reminder', [
                    'reminder_id' => $reminder->id,
                    'contract_id' => $contract->id,
                    'message' => $exception->getMessage(),
                ]);

                return redirect()
                    ->route('rental-contract-reminders.show', $token)
                    ->with('warning', 'تم تسجيل رغبتكم بتسليم العاملة، لكن تعذر تحديث حالة العقد تلقائياً. سيقوم الموظف المختص بمتابعة الطلب.');
            }

            return redirect()
                ->route('rental-contract-reminders.show', $token)
                ->with('success', 'تم تسجيل رغبتكم بتسليم العاملة وتحديث حالة العقد.');
        }

        $paymentUrl = $this->resolvePaymentUrl($contract, $reminder->fresh(), $moyasar, $reminderService);

        if ($paymentUrl) {
            return redirect()->away($paymentUrl);
        }

        return redirect()
            ->route('rental-contract-reminders.show', $token)
            ->with('warning', 'تم تسجيل رغبتكم بتمديد العقد، لكن رابط الدفع غير متاح حالياً. سيقوم الموظف المختص بالتواصل معكم.');
    }

    private function findReminder(string $token): RentalContractExpiryReminder
    {
        return RentalContractExpiryReminder::query()
            ->where('token', $token)
            ->firstOrFail();
    }

    private function resolvePaymentUrl(
        $contract,
        RentalContractExpiryReminder $reminder,
        MoyasarService $moyasar,
        RentalContractExpiryReminderService $reminderService
    ): ?string {
        if (!$contract->info) {
            return null;
        }

        if (
            !empty($reminder->payment_url)
            && !in_array((string) $reminder->payment_status, ['paid', 'failed', 'expired', 'refunded', 'partially_refunded'], true)
        ) {
            return $reminder->payment_url;
        }

        if (
            !empty($contract->info->moyasar_payment_url)
            && !in_array((string) $contract->info->moyasar_payment_status, ['paid', 'refunded', 'partially_refunded'], true)
        ) {
            return $contract->info->moyasar_payment_url;
        }

        if (!MoyasarSettings::isEnabled()) {
            return null;
        }

        $amount = $reminderService->renewalAmount($contract);
        if ($amount <= 0) {
            return null;
        }

        $contractNumber = $contract->contract_number ?: $contract->id;
        $clientName = $contract->user?->name ?? 'العميل';

        $callbackUrl = route('moyasar.callback', ['contract_id' => $contract->id, 'reminder_id' => $reminder->id]);
        $result = $moyasar->createPaymentLink(
            $amount,
            "تمديد عقد إيجار رقم {$contractNumber} - {$clientName}",
            $callbackUrl,
            [
                'contract_id' => $contract->id,
                'contract_number' => $contractNumber,
                'reminder_id' => $reminder->id,
                'source' => 'rental_contract_expiry_reminder',
            ],
            $callbackUrl
        );

        if (!$result || empty($result['id']) || empty($result['url'])) {
            Log::warning('Unable to create Moyasar payment link from expiry reminder', [
                'contract_id' => $contract->id,
                'reminder_id' => $reminder->id,
            ]);

            return null;
        }

        $payments = app(\App\Services\MoyasarContractPaymentService::class);
        $payments->register($contract->info, $result['id'], $amount, $reminder->id);

        $contract->info->update([
            'payment_type' => 'moyasar',
            'moyasar_payment_id' => $result['id'],
            'moyasar_payment_url' => $result['url'],
            'moyasar_payment_status' => 'initiated',
        ]);

        $reminderService->recordPaymentLink($reminder, $result['id'], $result['url']);

        return $result['url'];
    }
}
