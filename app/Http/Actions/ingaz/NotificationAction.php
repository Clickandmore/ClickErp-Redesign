<?php

namespace App\Http\Actions\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\MainAction;
use App\Http\Traits\Firebase;
use App\Http\Traits\SendSMSTrait;
use App\Models\ingaz\Notification;
use App\Models\ingaz\User;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NotificationAction extends MainAction
{
    use Firebase, SendSMSTrait;

    public function __construct(Notification $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Notification/';
    }


    public function storeNotification($data, Request $request): array
    {
        if ($request->input('type', 'sms') === 'sms') {
            return $this->storeSmsMessages($request);
        }

        return $this->storeGeneralNotifications($data, $request);
    }

    private function storeSmsMessages(Request $request): array
    {
        $senderNameType = (string) $request->input('sender_name_type', 'advertising');

        $template = SmsTemplate::query()
            ->whereKey($request->integer('sms_template_id'))
            ->where('is_active', true)
            ->first();

        if (!$template) {
            throw ValidationException::withMessages([
                'sms_template_id' => 'القالب المحدد غير موجود أو غير نشط.',
            ]);
        }

        $message = $this->buildSmsMessage($template, $request);
        $users = $this->resolveTargetUsers($request);

        if ($users->isEmpty()) {
            throw ValidationException::withMessages([
                'user_id' => 'لا يوجد عملاء صالحون للإرسال.',
            ]);
        }

        $this->assertBulkProtection($request, $users->count());

        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            $response = $this->sendSMS(formatSaudiPhone($user->phone), $message, null, $senderNameType);

            if (!$this->isSuccessfulSmsResponse($response)) {
                $failedCount++;
                continue;
            }

            $this->storeSmsLog($user, $template, $message);
            $sentCount++;
        }

        return [
            'template_id' => $template->id,
            'template_title' => $template->title,
            'sender_name_type' => $senderNameType,
            'total_count' => $users->count(),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ];
    }

    private function buildSmsMessage(SmsTemplate $template, Request $request): string
    {
        $variables = collect((array) $request->input('template_variables', []))
            ->mapWithKeys(function ($value, $key) {
                return [(string) $key => is_scalar($value) ? trim((string) $value) : ''];
            })
            ->toArray();

        $message = trim((string) $request->input('message_override', ''));
        if ($message === '') {
            $message = trim($template->processMessage($variables));
        }

        if ($message === '') {
            throw ValidationException::withMessages([
                'message_override' => 'نص الرسالة لا يمكن أن يكون فارغًا.',
            ]);
        }

        return $message;
    }

    private function resolveTargetUsers(Request $request): Collection
    {
        $usersQuery = User::query()
            ->where('is_blocked', false)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', 'name', 'phone');

        if ($request->input('all_or_some') === 'some') {
            $userIds = collect((array) $request->input('user_id', []))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $usersQuery->whereIn('id', $userIds);
        }

        return $usersQuery->get();
    }

    private function assertBulkProtection(Request $request, int $totalRecipients): void
    {
        if ($request->input('all_or_some') !== 'all') {
            return;
        }

        if (!$request->boolean('confirm_bulk_send')) {
            throw ValidationException::withMessages([
                'confirm_bulk_send' => 'يجب تفعيل تأكيد الإرسال الجماعي.',
            ]);
        }

        $keyword = strtoupper((string) config('msegat.bulk_confirmation_keyword', 'SEND_ALL_SMS'));
        $providedKeyword = strtoupper(trim((string) $request->input('bulk_confirmation_text')));
        if ($providedKeyword !== $keyword) {
            throw ValidationException::withMessages([
                'bulk_confirmation_text' => 'كلمة التأكيد غير صحيحة.',
            ]);
        }

        $bulkLimit = max(1, (int) config('msegat.bulk_send_limit', 200));
        if ($totalRecipients > $bulkLimit) {
            throw ValidationException::withMessages([
                'all_or_some' => "عدد المستلمين ({$totalRecipients}) يتجاوز الحد الآمن للإرسال الجماعي ({$bulkLimit}) في الدفعة الواحدة.",
            ]);
        }
    }

    private function storeSmsLog(User $user, SmsTemplate $template, string $message): void
    {
        $titleTranslations = [];
        $descriptionTranslations = [];

        foreach (config('translatable.locales', ['ar']) as $locale) {
            $titleTranslations[$locale] = $template->title;
            $descriptionTranslations[$locale] = $message;
        }

        $this->model->create([
            'title' => $titleTranslations,
            'description' => $descriptionTranslations,
            'user_id' => $user->id,
            'type' => 'sms',
        ]);
    }

    private function isSuccessfulSmsResponse($response): bool
    {
        if ($response === false || $response === null) {
            return false;
        }

        $raw = trim((string) $response);
        if ($raw === '') {
            return false;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return true;
        }

        if (isset($decoded['success'])) {
            return (bool) $decoded['success'];
        }

        if (isset($decoded['status'])) {
            return in_array(strtolower((string) $decoded['status']), ['ok', 'success'], true);
        }

        if (isset($decoded['code'])) {
            return in_array((string) $decoded['code'], ['1', '200'], true);
        }

        return true;
    }

    private function storeGeneralNotifications(array $data, Request $request): array
    {
        $usersIDs = [];
        $data['type'] = NotificationActionEnum::General->value;

        if ($request->all_or_some == 'all') {
            $data['user_id'] = null;
            $this->store($data);
            $usersIDs = User::where('is_blocked', false)->pluck('id')->toArray();
        } elseif ($request->all_or_some == 'some') {
            $usersIDs = (array) $request->user_id;
            foreach ($usersIDs as $user_id) {
                $data['user_id'] = $user_id;
                $this->store($data);
            }
        }

        foreach ($usersIDs as $to_user_id) {
            $data['user_id'] = $to_user_id;
            $this->sendFCMNotification($request->title, $request->description, $to_user_id, $data);
        }

        return [
            'type' => 'normal',
            'total_count' => count($usersIDs),
        ];
    }
}
