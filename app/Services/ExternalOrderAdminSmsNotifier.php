<?php

namespace App\Services;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\User;
use App\Support\CommunicationSettings;
use App\Support\SmsGateway;

class ExternalOrderAdminSmsNotifier
{
    public function send(RecruitmentRequest $order, User $user, Maid $maid, ?Admin $selectedMarketer): bool
    {
        $sms = CommunicationSettings::sms();
        $settings = (array) data_get($sms, 'external_order_admin_notification', []);

        if (
            (string) ($sms['gateway_status'] ?? 'on') === 'off'
            || !filter_var($settings['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ) {
            return false;
        }

        $mode = (string) ($settings['recipient_mode'] ?? 'selected_marketer');
        $rawPhone = $mode === 'unified'
            ? ($settings['unified_phone'] ?? null)
            : $selectedMarketer?->contact_phone;
        $phone = $rawPhone ? formatSaudiPhone($rawPhone) : null;

        if ($phone && !preg_match('/^9665\d{8}$/', preg_replace('/\D+/', '', $phone))) {
            $phone = null;
        }

        if (!$phone) {
            logger()->warning('External order admin SMS skipped: recipient phone is unavailable.', [
                'order_id' => $order->id,
                'recipient_mode' => $mode,
                'selected_marketer_id' => $selectedMarketer?->id,
            ]);
            return false;
        }

        $clientPhone = formatSaudiPhone($user->phone) ?: $user->phone;
        $maidName = $maid->name ?: ('#' . $maid->id);
        $message = "حجز جديد من الموقع الخارجي\n"
            . "رقم الطلب: {$order->order_code}\n"
            . "العميل: {$user->name}\n"
            . "الجوال: {$clientPhone}\n"
            . "العاملة: {$maidName}\n"
            . 'يرجى التواصل مع العميل في أقرب وقت.';

        try {
            return SmsGateway::send($phone, $message, null, 'advertising') !== null;
        } catch (\Throwable $exception) {
            logger()->error('External order admin SMS failed.', [
                'order_id' => $order->id,
                'recipient_mode' => $mode,
                'selected_marketer_id' => $selectedMarketer?->id,
                'error' => $exception->getMessage(),
            ]);
            return false;
        }
    }
}
