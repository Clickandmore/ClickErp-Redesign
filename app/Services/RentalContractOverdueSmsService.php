<?php

namespace App\Services;

use App\Models\RentalContract;
use App\Support\CommunicationSettings;
use App\Support\OutboundMessageRecorder;
use App\Support\SmsGateway;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class RentalContractOverdueSmsService
{
    public function daysOverdue(RentalContract $contract): int
    {
        if (!$contract->info?->ingaz_to || $contract->closed_at
            || in_array($contract->status, ['completed', 'cancelled'], true)) {
            return 0;
        }

        return max(0, (int) Carbon::parse($contract->info->ingaz_to)->startOfDay()->diffInDays(today(), false));
    }

    public function message(RentalContract $contract): string
    {
        $days = $this->daysOverdue($contract);
        if ($days < 1) {
            throw ValidationException::withMessages(['contract' => 'هذا الإجراء متاح فقط للعقود التي تجاوزت تاريخ الانتهاء ولم يتم إغلاقها.']);
        }

        $number = $contract->contract_number ?: $contract->id;
        $date = Carbon::parse($contract->info->ingaz_to)->format('Y-m-d');

        return "عميلنا {$contract->user?->name}، انتهى عقد التأجير رقم {$number} بتاريخ {$date}، وقد مضى على انتهائه {$days} يوم. يرجى التواصل معنا لترتيب التمديد أو التسليم. شكراً لتعاملكم معنا.";
    }

    public function send(RentalContract $contract): void
    {
        $contract->loadMissing(['user', 'info']);
        $message = $this->message($contract);
        $phone = (string) ($contract->user?->phone ?? '');
        if ($phone === '') {
            throw ValidationException::withMessages(['phone' => 'لا يوجد رقم جوال للعميل.']);
        }
        if (!CommunicationSettings::smsIsConfigured()) {
            throw ValidationException::withMessages(['sms' => 'إعدادات الرسائل النصية غير مكتملة أو بوابة SMS متوقفة.']);
        }
        $phone = formatSaudiPhone((string) $contract->user->phone_code . $phone);
        // Every deliberate send is a fresh attempt; SmsGateway records each one.
        $result = $this->dispatch($phone, $message, [
            'source' => 'rental_contract_overdue_sms',
            'contract_id' => $contract->id,
            'user_id' => $contract->user_id,
            'branch_id' => $contract->branch_id,
            'admin_id' => auth('admin')->id(),
        ]);
        if (!OutboundMessageRecorder::smsAccepted($result, $phone)) {
            throw ValidationException::withMessages(['sms' => 'لم يقبل مزود الرسائل الإرسال. راجع سجل الرسائل ثم أعد المحاولة.']);
        }
    }

    protected function dispatch(string $phone, string $message, array $context): ?string
    {
        return SmsGateway::send($phone, $message, null, null, $context);
    }
}
