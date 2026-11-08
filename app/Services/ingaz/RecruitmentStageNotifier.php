<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\RecruitmentContractStageStep;
use App\Models\RecruitmentContractStepCompletion;
use App\Models\RecruitmentStageNotification;
use App\Services\TeqnyatSmsService;
use App\Support\RecruitmentContractStageLabels;
use App\Support\RecruitmentStageNotificationSettings as Settings;
use App\Support\RecruitmentStagePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RecruitmentStageNotifier
{
    /** @var array<int, true> عقود جُدول إرسالها في نهاية الطلب الحالي */
    private static array $deliveryScheduled = [];

    public function stageChanged(Contract $contract, string $previous): void
    {
        if (!RecruitmentStagePolicy::isForward($previous, $contract->status)) {
            return;
        }
        $settings = Settings::all();
        $rule = $settings['stages'][$contract->status] ?? [];
        $this->record($contract, 'stage:'.$contract->status, $rule, !empty($settings['enabled']));
    }

    public function stepCompleted(Contract $contract, RecruitmentContractStageStep $step): void
    {
        $settings = Settings::all();
        $this->record($contract, 'step:'.$step->id, [
            'sms' => $step->notify_sms, 'email' => $step->notify_email,
            'template' => $step->notification_template,
        ], !empty($settings['enabled']), $step);
    }

    private function record(Contract $contract, string $event, array $rule, bool $enabled, ?RecruitmentContractStageStep $step = null): void
    {
        if (!$enabled || !in_array($contract->type, ['recruitment', 'maid'], true)) {
            return;
        }
        $client = $contract->user;
        $stage = RecruitmentContractStageLabels::label($contract->status, 'ar') ?: $contract->status;
        $message = strtr(trim((string) ($rule['template'] ?? '')) ?: Settings::TEMPLATE, [
            '{client_name}' => (string) ($client?->name ?? ''), '{contract_number}' => (string) $contract->id,
            '{stage_name}' => $stage, '{step_name}' => $step ? ' — تم إنجاز '.$step->displayName('ar') : '',
        ]);
        $queued = false;
        foreach (['sms' => $client?->phone, 'email' => $client?->email] as $channel => $recipient) {
            if (empty($rule[$channel])) {
                continue;
            }
            $valid = $channel === 'email' ? filter_var($recipient, FILTER_VALIDATE_EMAIL) : filled($recipient);
            $inserted = RecruitmentStageNotification::insertOrIgnore([
                'contract_id' => $contract->id, 'event_key' => $event, 'channel' => $channel,
                'recipient' => $recipient, 'subject' => 'تحديث عقد الاستقدام رقم '.$contract->id,
                'message' => $message, 'status' => $valid ? 'pending' : 'skipped',
                'error' => $valid ? null : 'بيانات التواصل غير متوفرة أو غير صالحة.',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $queued = $queued || ($valid && $inserted > 0);
        }
        if ($queued) {
            $this->deliverAfterCommit((int) $contract->id);
        }
    }

    /**
     * يُرسل فور نجاح حفظ التغيير وبعد إرسال الرد للموظف، دون انتظار المجدول.
     * التغيير المتراجع عنه لا يُرسل شيئًا، والمجدول يبقى شبكة أمان لما يفشل هنا.
     */
    private function deliverAfterCommit(int $contractId): void
    {
        DB::afterCommit(function () use ($contractId) {
            if (isset(self::$deliveryScheduled[$contractId])) {
                return;
            }
            self::$deliveryScheduled[$contractId] = true;
            app()->terminating(function () use ($contractId) {
                unset(self::$deliveryScheduled[$contractId]);
                try {
                    $this->deliverPending($contractId);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });
        });
    }

    public function deliverPending(int $contractId): void
    {
        if (empty(Settings::all()['enabled'])) {
            return;
        }
        RecruitmentStageNotification::where('contract_id', $contractId)->where('status', 'pending')
            ->orderBy('id')->get()->each(fn ($notification) => $this->deliver($notification));
    }

    public function deliver(RecruitmentStageNotification $notification): void
    {
        // Claim before contacting a provider: overlapping workers cannot send the same row.
        if (!RecruitmentStageNotification::whereKey($notification->id)->where('status', 'pending')->update(['status' => 'processing'])) {
            return;
        }
        try {
            $contract = Contract::find($notification->contract_id);
            $recipient = $notification->channel === 'email' ? $contract?->user?->email : $contract?->user?->phone;
            $eventValid = false;
            if ($contract && str_starts_with($notification->event_key, 'stage:')) {
                $stage = substr($notification->event_key, 6);
                $eventValid = isset(RecruitmentStagePolicy::RANK[$contract->status], RecruitmentStagePolicy::RANK[$stage])
                    && RecruitmentStagePolicy::RANK[$contract->status] >= RecruitmentStagePolicy::RANK[$stage];
            } elseif ($contract && str_starts_with($notification->event_key, 'step:')) {
                $eventValid = isset(RecruitmentStagePolicy::RANK[$contract->status])
                    && RecruitmentContractStepCompletion::where('contract_id', $contract->id)
                        ->where('stage_step_id', substr($notification->event_key, 5))->whereNotNull('completed_at')->exists();
            }
            if (!$eventValid || (string) $recipient !== (string) $notification->recipient) {
                $notification->update(['status' => 'cancelled', 'error' => 'ألغي الإرسال لتغير تقدم العقد أو بيانات العميل.']);
                return;
            }
            if ($notification->channel === 'email') {
                \App\Support\OutboundMessageRecorder::withContext(
                    \App\Services\ContractMessageHistoryService::context($contract) + ['source' => 'recruitment_stage'],
                    fn () => Mail::raw($notification->message, fn ($mail) => $mail->to($notification->recipient)->subject($notification->subject))
                );
                $status = 'sent';
                $error = null;
            } else {
                $result = app(TeqnyatSmsService::class)->sendMessage($notification->recipient, $notification->message, [
                    'source' => 'recruitment_stage', 'contract_id' => $notification->contract_id,
                ]);
                $status = $result['status'] ?? 'failed';
                $error = $status === 'sent' ? null : ($result['message'] ?? 'تعذر إرسال الرسالة.');
            }
            $notification->update(['status' => $status, 'error' => $error, 'sent_at' => $status === 'sent' ? now() : null]);
        } catch (\Throwable $exception) {
            report($exception);
            $notification->update(['status' => 'failed', 'error' => 'تعذر الإرسال؛ راجع إعدادات القناة وسجل النظام.']);
        }
    }
}
