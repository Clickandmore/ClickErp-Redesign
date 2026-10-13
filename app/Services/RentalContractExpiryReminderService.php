<?php

namespace App\Services;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Traits\SendSMSTrait;
use App\Models\AdminNotification;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\RentalContractInfo;
use App\Models\RentalContractExpiryReminder;
use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\CommunicationSettings;
use App\Support\NotificationAlertSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RentalContractExpiryReminderService
{
    use SendSMSTrait;

    public function __construct(private readonly WhatsAppMessagingService $whatsApp)
    {
    }

    public function isEnabled(): bool
    {
        $rule = NotificationAlertSettings::rule('rent_contract_expiry');
        $channels = (array) ($rule['channels'] ?? []);

        return (bool) ($rule['enabled'] ?? false)
            && in_array((string) ($rule['recipient'] ?? ''), ['client', 'both'], true)
            && (in_array('sms', $channels, true) || in_array('whatsapp', $channels, true));
    }

    public function reminderDays(): int
    {
        return max(1, (int) ceil(
            NotificationAlertSettings::leadTimeInMinutes('rent_contract_expiry') / 1440
        ));
    }

    public function channel(): string
    {
        $channels = (array) data_get(
            NotificationAlertSettings::rule('rent_contract_expiry'),
            'channels',
            []
        );

        if (in_array('sms', $channels, true) && in_array('whatsapp', $channels, true)) {
            return 'both';
        }

        if (in_array('whatsapp', $channels, true)) {
            return 'whatsapp';
        }

        return in_array('sms', $channels, true) ? 'sms' : 'none';
    }

    public function createAndSendReminder(
        RentalContract $contract,
        string $notificationType = 'advance'
    ): ?RentalContractExpiryReminder
    {
        if (!RentalContract::query()->eligibleForExpiryNotifications()->whereKey($contract->id)->exists()) {
            return null;
        }
        $contract->loadMissing(['user', 'info', 'marketer']);

        if (!$contract->info?->ingaz_to || !$contract->user) {
            return null;
        }

        $expiresOn = $contract->info->ingaz_to->toDateString();

        $existingReminder = RentalContractExpiryReminder::query()
            ->where('rental_contract_id', $contract->id)
            ->whereDate('expires_on', $expiresOn)
            ->where('notification_type', $notificationType)
            ->first();

        if ($existingReminder) {
            if ($existingReminder->sms_sent || $existingReminder->whatsapp_sent || $existingReminder->responded_at) {
                return $existingReminder;
            }

            $existingReminder->update([
                'channel' => $this->channel(),
                'send_error' => null,
            ]);

            return $this->sendReminder($contract, $existingReminder->fresh());
        }

        $reminder = RentalContractExpiryReminder::create([
            'rental_contract_id' => $contract->id,
            'token' => Str::random(64),
            'channel' => $this->channel(),
            'notification_type' => $notificationType,
            'status' => 'pending',
            'expires_on' => $expiresOn,
        ]);

        return $this->sendReminder($contract, $reminder);
    }

    private function sendReminder(RentalContract $contract, RentalContractExpiryReminder $reminder): RentalContractExpiryReminder
    {
        $message = $this->buildCustomerMessage($contract, $reminder);
        $phone = $this->customerPhone($contract);
        $smsSent = false;
        $whatsappSent = false;
        $errors = [];

        if ($phone === '') {
            $this->recordUnsentReminder($contract, $reminder, $message, $phone, $reminder->channel, 'لا يوجد رقم جوال للعميل.');
            $errors[] = 'لا يوجد رقم جوال للعميل.';
        } else {
            if (in_array($reminder->channel, ['sms', 'both'], true)) {
                if (!CommunicationSettings::smsIsConfigured()) {
                    $this->recordUnsentReminder($contract, $reminder, $message, $phone, 'sms', 'إعدادات SMS غير مكتملة أو البوابة متوقفة.');
                    $errors[] = 'SMS: إعدادات الرسائل النصية غير مكتملة أو بوابة SMS متوقفة.';
                } else {
                    try {
                        $smsResult = \App\Support\SmsGateway::send(formatSaudiPhone($phone), $message, null, null, [
                            'source' => 'rental_contract_expiry_reminder', 'contract_id' => $contract->id,
                            'reminder_id' => $reminder->id, 'user_id' => $contract->user_id, 'branch_id' => $contract->branch_id,
                        ]);
                        $smsSent = $this->smsWasSent($smsResult);

                        if (!$smsSent) {
                            $errors[] = 'SMS: ' . $this->describeSmsFailure($smsResult);
                        }
                    } catch (\Throwable $exception) {
                        $errors[] = 'SMS: ' . $exception->getMessage();
                    }
                }
            }

            if (in_array($reminder->channel, ['whatsapp', 'both'], true)) {
                if (!$this->whatsApp->isConfigured()) {
                    $this->recordUnsentReminder($contract, $reminder, $message, $phone, 'whatsapp', 'إعدادات واتساب غير مكتملة.');
                    $missing = $this->whatsApp->missingConfigKeys();
                    $errors[] = 'WhatsApp: إعدادات واتساب غير مكتملة'
                        . ($missing ? ' (' . implode(', ', $missing) . ')' : '')
                        . '.';
                } else {
                    try {
                        $result = $this->whatsApp->sendText($phone, $message, [
                            'source' => 'rental_contract_expiry_reminder',
                            'contract_id' => $contract->id,
                            'reminder_id' => $reminder->id,
                            'user_id' => $contract->user_id,
                            'branch_id' => $contract->branch_id,
                        ]);
                        $whatsappSent = (bool) ($result['success'] ?? false);
                        if (!$whatsappSent) {
                            $errors[] = 'WhatsApp: ' . $this->summarizeProviderResponse($result['response'] ?? $result);
                        }
                    } catch (\Throwable $exception) {
                        $errors[] = 'WhatsApp: ' . $exception->getMessage();
                    }
                }
            }
        }

        $reminder->update([
            'sent_at' => ($smsSent || $whatsappSent) ? now() : null,
            'sms_sent' => $smsSent,
            'whatsapp_sent' => $whatsappSent,
            'send_error' => $errors ? implode(' | ', $errors) : null,
        ]);

        if (!$smsSent && !$whatsappSent) {
            Log::warning('Rental contract expiry reminder was not delivered', [
                'contract_id' => $contract->id,
                'reminder_id' => $reminder->id,
                'errors' => $errors,
            ]);
        }

        return $reminder->fresh();
    }

    private function recordUnsentReminder(RentalContract $contract, RentalContractExpiryReminder $reminder, string $message, string $phone, string $channel, string $error): void
    {
        foreach ($channel === 'both' ? ['sms', 'whatsapp'] : [$channel] as $item) {
            $provider = $item === 'sms' ? (string) (CommunicationSettings::sms()['provider'] ?? 'msegat') : $this->whatsApp->driver();
            $log = \App\Support\OutboundMessageRecorder::begin($item, $phone, $message, $provider, [
                'source' => 'rental_contract_expiry_reminder', 'contract_id' => $contract->id,
                'reminder_id' => $reminder->id, 'user_id' => $contract->user_id, 'branch_id' => $contract->branch_id,
            ]);
            \App\Support\OutboundMessageRecorder::finish($log, 'skipped', ['error_message' => $error]);
        }
    }

    public function markCustomerResponse(RentalContractExpiryReminder $reminder, string $response): void
    {
        $status = $response === 'extend' ? 'responded_extend' : 'responded_return';

        $updates = [
            'status' => $status,
            'response' => $response,
        ];

        if (!$reminder->responded_at || $reminder->response !== $response) {
            $updates['responded_at'] = now();
        }

        $reminder->update($updates);

        $this->notifyResponsibleEmployee($reminder->fresh(), $response);
    }

    public function recordPaymentLink(RentalContractExpiryReminder $reminder, string $paymentId, string $paymentUrl): void
    {
        $reminder->update([
            'payment_id' => $paymentId,
            'payment_url' => $paymentUrl,
            'payment_status' => 'initiated',
            'contract_effect_error' => null,
        ]);
    }

    public function applyReturnDecision(RentalContractExpiryReminder $reminder): void
    {
        DB::transaction(function () use ($reminder) {
            $lockedReminder = RentalContractExpiryReminder::query()
                ->lockForUpdate()
                ->findOrFail($reminder->id);

            if ($lockedReminder->contract_effect_applied_at) {
                return;
            }

            $contract = RentalContract::query()
                ->with(['info', 'maid'])
                ->lockForUpdate()
                ->findOrFail($lockedReminder->rental_contract_id);

            if (!$contract->info) {
                $this->markContractEffectFailed($lockedReminder, 'return', 'بيانات عقد الإيجار غير مكتملة.');
                throw new \RuntimeException('بيانات عقد الإيجار غير مكتملة.');
            }

            if ($contract->status === 'completed' && $contract->closure_type === 'available') {
                $lockedReminder->update([
                    'contract_effect_type' => 'return',
                    'contract_effect_applied_at' => now(),
                    'contract_effect_error' => null,
                ]);

                return;
            }

            if (!$contract->canCloseOrExtend()) {
                $message = 'لا يمكن إكمال العقد لأن حالته الحالية ليست نشطة.';
                $this->markContractEffectFailed($lockedReminder, 'return', $message);
                throw new \RuntimeException($message);
            }

            $closureDate = now()->startOfDay();

            $contract->forceFill([
                'status' => 'completed',
                'closure_type' => 'available',
                'closed_at' => $closureDate,
                'warranty_status' => 'active',
                'warranty_start_date' => $closureDate,
                'warranty_end_date' => $closureDate->copy()->addMonths(3),
            ])->save();

            $contract->info->update([
                'closure_return_reason' => 'اختار العميل تسليم العاملة من رابط تنبيه انتهاء عقد الإيجار.',
            ]);

            $this->completeLinkedRentalOrder($contract);

            if ($contract->maid_id) {
                $maid = Maid::query()->lockForUpdate()->find($contract->maid_id);
                if ($maid) {
                    $this->syncMaidBookingState($maid);
                    $this->markRentalMaidAsAvailable($maid, $closureDate);
                }
            }

            $this->returnShelterContractInsideForRental($contract);

            $lockedReminder->update([
                'contract_effect_type' => 'return',
                'contract_effect_applied_at' => now(),
                'contract_effect_error' => null,
            ]);
        });
    }

    public function applyPaidExtensionForMoyasarPayment(
        RentalContractInfo $info,
        ?string $paymentId = null,
        ?int $reminderId = null
    ): void {
        $reminder = $this->findExtendReminderForPayment($info, $paymentId, $reminderId);

        if (!$reminder) {
            return;
        }

        DB::transaction(function () use ($reminder, $info, $paymentId) {
            $lockedReminder = RentalContractExpiryReminder::query()
                ->lockForUpdate()
                ->findOrFail($reminder->id);

            if ($lockedReminder->contract_effect_applied_at) {
                return;
            }

            $contract = RentalContract::query()
                ->with(['info'])
                ->lockForUpdate()
                ->findOrFail($lockedReminder->rental_contract_id);

            if (!$contract->info) {
                $this->markContractEffectFailed($lockedReminder, 'extend', 'بيانات عقد الإيجار غير مكتملة.');
                throw new \RuntimeException('بيانات عقد الإيجار غير مكتملة.');
            }

            $durationDays = $this->renewalDurationDays($contract);
            if ($durationDays <= 0) {
                $this->markContractEffectFailed($lockedReminder, 'extend', 'مدة التمديد غير محددة في بيانات العقد.');
                throw new \RuntimeException('مدة التمديد غير محددة في بيانات العقد.');
            }

            $baseEndDate = $contract->info->ingaz_to
                ? Carbon::parse($contract->info->ingaz_to)
                : now()->startOfDay();

            if ($baseEndDate->lt(now()->startOfDay())) {
                $baseEndDate = now()->startOfDay();
            }

            $extensionStart = $baseEndDate->copy()->addDay();
            $extensionEnd = $baseEndDate->copy()->addDays($durationDays);
            $extensionMonths = (int) floor($durationDays / 30);
            $extensionValue = $this->renewalAmount($contract);

            $extension = RentalContractExtension::create([
                'rental_contract_id' => $contract->id,
                'extension_months' => $extensionMonths,
                'extension_days' => $durationDays,
                'extension_value' => $extensionValue,
                'start_date' => $extensionStart,
                'end_date' => $extensionEnd,
                'payment_status' => 'paid',
                'payment_receipt_path' => null,
                'notes' => 'تمديد تلقائي بعد دفع العميل من رابط تنبيه انتهاء عقد الإيجار.',
                'created_by' => $contract->marketer_id,
            ]);

            $contract->info->update([
                'moyasar_payment_status' => 'paid',
                'moyasar_paid_at' => now(),
                'payment_confirmed_at' => now(),
                'ingaz_to' => $extensionEnd,
            ]);

            $contract->update([
                'status' => 'active',
                'closure_type' => null,
                'closed_at' => null,
                'status_before_freeze' => null,
                'frozen_at' => null,
            ]);

            $lockedReminder->update([
                'payment_id' => $paymentId ?: $lockedReminder->payment_id ?: $info->moyasar_payment_id,
                'payment_status' => 'paid',
                'extension_id' => $extension->id,
                'contract_effect_type' => 'extend',
                'contract_effect_applied_at' => now(),
                'contract_effect_error' => null,
            ]);
        });
    }

    public function notifyResponsibleEmployee(RentalContractExpiryReminder $reminder, string $response): void
    {
        $contract = $reminder->rentalContract()->with(['user', 'maid', 'marketer'])->first();

        if (!$contract) {
            return;
        }

        $responseLabel = $response === 'extend' ? 'تمديد العقد' : 'تسليم العاملة';
        $title = 'رد العميل على تنبيه انتهاء عقد الإيجار';
        $message = 'العميل ' . ($contract->user?->name ?? 'غير محدد')
            . ' اختار: ' . $responseLabel
            . ' لعقد الإيجار رقم ' . ($contract->contract_number ?: $contract->id) . '.';

        $adminIds = $contract->marketer_id
            ? [$contract->marketer_id]
            : Admin::query()->pluck('id')->all();

        $adminIds = array_filter(array_unique($adminIds));

        foreach ($adminIds as $adminId) {
            $alreadyNotified = AdminNotification::query()
                ->where('admin_id', $adminId)
                ->where('type', 'rental_contract_expiry_response')
                ->where('link', route('RentContract.show', $contract->id))
                ->where('data->reminder_id', $reminder->id)
                ->where('data->response', $response)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            AdminNotification::create([
                'admin_id' => $adminId,
                'created_by' => null,
                'title' => $title,
                'message' => $message,
                'type' => 'rental_contract_expiry_response',
                'link' => route('RentContract.show', $contract->id),
                'data' => [
                    'rental_contract_id' => $contract->id,
                    'reminder_id' => $reminder->id,
                    'response' => $response,
                ],
            ]);
        }

        $this->sendResponsibleEmployeeSms($reminder->fresh(), $contract, $responseLabel, $adminIds);
    }

    private function sendResponsibleEmployeeSms(
        RentalContractExpiryReminder $reminder,
        RentalContract $contract,
        string $responseLabel,
        array $adminIds
    ): void {
        if ($reminder->employee_sms_sent_at) {
            return;
        }

        $admins = Admin::query()
            ->whereIn('id', $adminIds)
            ->get();

        if ($admins->isEmpty()) {
            $reminder->update([
                'employee_sms_error' => 'لا يوجد موظف مسؤول لإرسال رسالة SMS.',
            ]);

            return;
        }

        if (!CommunicationSettings::smsIsConfigured()) {
            $reminder->update([
                'employee_sms_error' => 'إعدادات SMS غير مكتملة أو بوابة SMS متوقفة.',
            ]);

            return;
        }

        $message = $this->buildEmployeeResponseMessage($contract, $responseLabel);
        $sentRecipients = [];
        $errors = [];

        foreach ($admins as $admin) {
            $phone = $admin->formatted_contact_phone ?: $admin->formatted_whatsapp_phone;

            if (!$phone) {
                $errors[] = 'لا يوجد رقم جوال للموظف ' . ($admin->name ?: ('#' . $admin->id)) . '.';
                continue;
            }

            try {
                $smsResult = $this->sendSMS($phone, $message);

                if ($this->smsWasSent($smsResult)) {
                    $sentRecipients[] = [
                        'admin_id' => $admin->id,
                        'name' => $admin->name,
                        'phone' => $phone,
                    ];

                    continue;
                }

                $errors[] = 'فشل إرسال SMS للموظف ' . ($admin->name ?: ('#' . $admin->id))
                    . ': ' . $this->describeSmsFailure($smsResult);
            } catch (\Throwable $exception) {
                $errors[] = 'فشل إرسال SMS للموظف ' . ($admin->name ?: ('#' . $admin->id))
                    . ': ' . $exception->getMessage();
            }
        }

        $reminder->update([
            'employee_sms_sent_at' => $sentRecipients ? now() : null,
            'employee_sms_recipients' => $sentRecipients ?: null,
            'employee_sms_error' => $errors ? implode(' | ', $errors) : null,
        ]);

        if (!$sentRecipients) {
            Log::warning('Rental contract customer response SMS was not delivered to employee', [
                'contract_id' => $contract->id,
                'reminder_id' => $reminder->id,
                'admin_ids' => $adminIds,
                'errors' => $errors,
            ]);
        }
    }

    private function buildEmployeeResponseMessage(RentalContract $contract, string $responseLabel): string
    {
        $contractNumber = $contract->contract_number ?: $contract->id;
        $clientName = $contract->user?->name ?: 'غير محدد';
        $maidName = $contract->maid?->name ?: 'غير محددة';
        $contractUrl = route('RentContract.show', $contract->id);

        return implode("\n", [
            'تنبيه عقد إيجار',
            "العميل {$clientName} اختار: {$responseLabel}.",
            "رقم العقد: {$contractNumber}.",
            "العاملة: {$maidName}.",
            "رابط العقد: {$contractUrl}",
        ]);
    }

    private function findExtendReminderForPayment(
        RentalContractInfo $info,
        ?string $paymentId = null,
        ?int $reminderId = null
    ): ?RentalContractExpiryReminder {
        $query = RentalContractExpiryReminder::query()
            ->where('rental_contract_id', $info->rental_contract_id)
            ->where('response', 'extend')
            ->whereNull('contract_effect_applied_at')
            ->latest('responded_at')
            ->latest('id');

        if ($reminderId) {
            return (clone $query)->where('id', $reminderId)->first();
        }

        if ($paymentId) {
            $matched = (clone $query)->where('payment_id', $paymentId)->first();

            if ($matched) {
                return $matched;
            }
        }

        return $query->first();
    }

    private function markContractEffectFailed(
        RentalContractExpiryReminder $reminder,
        string $type,
        string $message
    ): void {
        $reminder->update([
            'contract_effect_type' => $type,
            'contract_effect_error' => $message,
        ]);
    }

    private function renewalDurationDays(RentalContract $contract): int
    {
        $durationDays = (int) ($contract->info?->contract_duration_days ?? 0);

        if ($durationDays > 0) {
            return $durationDays;
        }

        return max(0, (int) ($contract->info?->contract_duration_months ?? 0) * 30);
    }

    public function renewalAmount(RentalContract $contract): float
    {
        $contract->loadMissing('info');

        $durationDays = $this->renewalDurationDays($contract);
        $contractValue = (float) ($contract->info?->contract_value ?? 0);

        if ($contractValue > 0) {
            return round($contractValue, 2);
        }

        $monthlyCost = (float) ($contract->info?->monthly_cost ?? 0);

        if ($monthlyCost > 0 && $durationDays > 0) {
            return round(($monthlyCost / 30) * $durationDays, 2);
        }

        return 0.0;
    }

    private function completeLinkedRentalOrder(RentalContract $contract): void
    {
        if (!$contract->order_id) {
            return;
        }

        $order = Order::query()->lockForUpdate()->find($contract->order_id);

        if (!$order) {
            return;
        }

        $order->update([
            'status' => OrderEnum::Completed->value,
            'status_changed_at' => now(),
        ]);
    }

    private function syncMaidBookingState(Maid $maid): void
    {
        if ($maid->status === 'backout') {
            $maid->update([
                'available' => false,
            ]);

            return;
        }

        $hasActiveRentalContracts = RentalContract::query()
            ->where('maid_id', $maid->id)
            ->whereIn('status', $this->rentalActiveContractStatuses())
            ->exists();

        if ($hasActiveRentalContracts) {
            $maid->update([
                'status' => 'contracted',
                'available' => false,
            ]);

            return;
        }

        $maid->update([
            'status' => 'available',
            'available' => true,
        ]);
    }

    private function rentalActiveContractStatuses(): array
    {
        return [
            'new',
            'waiting_contract_send',
            'contract_sent',
            'contract_signed',
            'maid_delivered',
            'active',
            'suspended',
            'frozen',
        ];
    }

    private function markRentalMaidAsAvailable(Maid $maid, Carbon $availableAt): void
    {
        $maid->forceFill([
            'available' => true,
            'status' => 'available',
            'is_show' => true,
            'available_at' => $availableAt->toDateString(),
        ])->save();
    }

    private function returnShelterContractInsideForRental(RentalContract $rentalContract): void
    {
        app(RentalShelterPresenceService::class)->returnInside($rentalContract);
    }

    private function buildCustomerMessage(RentalContract $contract, RentalContractExpiryReminder $reminder): string
    {
        $clientName = $contract->user?->name ?: '';
        $contractNumber = $contract->contract_number ?: $contract->id;
        $expiresOn = optional($contract->info?->ingaz_to)->format('Y-m-d');
        // Use the contact-based wording even when an older template contains a response link.
        return \App\Support\RentalExpiryMessage::customer(
            (string) $clientName,
            (string) $contractNumber,
            (string) $expiresOn,
            $reminder->notification_type === 'expired'
        );
    }

    private function customerPhone(RentalContract $contract): string
    {
        return trim((string) ($contract->user?->phone_code ?? '') . (string) ($contract->user?->phone ?? ''));
    }

    private function summarizeProviderResponse($response): string
    {
        if ($response === null || $response === false || (is_string($response) && trim($response) === '')) {
            return 'لا توجد استجابة من مزود الخدمة.';
        }

        $text = is_scalar($response)
            ? (string) $response
            : json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return Str::limit($text ?: 'استجابة غير معروفة.', 220);
    }

    private function smsWasSent($result): bool
    {
        if (is_array($result)) {
            return $this->smsDecodedResultWasSent($result);
        }

        if ($result === null || $result === false || trim((string) $result) === '') {
            return false;
        }

        $decoded = json_decode((string) $result, true);
        if (is_array($decoded)) {
            return $this->smsDecodedResultWasSent($decoded);
        }

        return in_array(strtolower(trim((string) $result)), ['1', '200', 'success', 'sent', 'accepted'], true);
    }

    private function smsDecodedResultWasSent(array $decoded): bool
    {
        if (array_key_exists('success', $decoded)) {
            return (bool) $decoded['success'];
        }

        if (isset($decoded['code']) && in_array((string) $decoded['code'], ['1', '200'], true)) {
            return true;
        }

        if (
            isset($decoded['statusCode'])
            && in_array((string) $decoded['statusCode'], ['200', '201'], true)
            && !empty($decoded['messageId'])
        ) {
            return true;
        }

        if (isset($decoded['rejected']) && $this->hasProviderItems($decoded['rejected'])) {
            return false;
        }

        if (isset($decoded['accepted'])) {
            return $this->hasProviderItems($decoded['accepted']);
        }

        return isset($decoded['statusCode']) && in_array((string) $decoded['statusCode'], ['200', '201'], true);
    }

    private function describeSmsFailure($result): string
    {
        $decoded = is_array($result) ? $result : json_decode((string) $result, true);

        if (is_array($decoded)) {
            $rejectedNumbers = $this->providerItems($decoded['rejected'] ?? []);
            if ($rejectedNumbers !== []) {
                return 'مزود الرسائل رفض الرقم: ' . implode(', ', $rejectedNumbers)
                    . '. تحقق من صحة رقم العميل، صيغة الرقم الدولية، ورصيد/صلاحية حساب SMS.';
            }

            if (isset($decoded['accepted']) && !$this->hasProviderItems($decoded['accepted'])) {
                return 'مزود الرسائل لم يقبل أي رقم للإرسال. تحقق من رقم العميل وإعدادات مرسل SMS.';
            }

            if (isset($decoded['statusCode']) || isset($decoded['message'])) {
                return 'استجابة مزود الرسائل لا تؤكد الإرسال. التفاصيل: ' . $this->summarizeProviderResponse($decoded);
            }
        }

        return 'لم يرجع مزود الرسائل تأكيد نجاح. الاستجابة: ' . $this->summarizeProviderResponse($result);
    }

    private function hasProviderItems($value): bool
    {
        return $this->providerItems($value) !== [];
    }

    private function providerItems($value): array
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? [] : [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];
        array_walk_recursive($value, function ($item) use (&$items) {
            $item = trim((string) $item);
            if ($item !== '') {
                $items[] = $item;
            }
        });

        return array_values(array_unique($items));
    }
}
