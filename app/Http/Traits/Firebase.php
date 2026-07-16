<?php

namespace App\Http\Traits;

use App\Models\ingaz\UserFirebaseToken;
use App\Services\Firebase\FirebasePushService;

/**
 * واجهة توافقية للكود القديم.
 *
 * التنفيذ الفعلي انتقل إلى App\Services\Firebase\FirebasePushService بعد إغلاق
 * جوجل لواجهة FCM القديمة (fcm.googleapis.com/fcm/send) في يونيو 2024.
 * التوقيع لم يتغيّر حتى تستمر مواضع الاستدعاء الحالية بالعمل كما هي.
 */
trait Firebase
{
    /**
     * @param  string     $title
     * @param  string     $message
     * @param  int|null   $id       معرّف المستخدم المستلم
     * @param  array      $data     حمولة إضافية تصل للتطبيق
     * @return array  حصيلة الإرسال (success_count / failure_count / …)
     */
    public function sendFCMNotification($title, $message, $id, $data = [])
    {
        if (empty($id)) {
            return ['success_count' => 0, 'failure_count' => 0, 'skipped' => true];
        }

        return app(FirebasePushService::class)
            ->sendToUser((int) $id, (string) $title, (string) $message, (array) $data)
            ->toArray();
    }

    public function getRecipientTokens($userId)
    {
        return UserFirebaseToken::where('user_id', $userId)->pluck('phone_token')->toArray();
    }
}
