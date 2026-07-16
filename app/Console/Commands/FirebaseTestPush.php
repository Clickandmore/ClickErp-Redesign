<?php

namespace App\Console\Commands;

use App\Services\Firebase\FirebasePushService;
use Illuminate\Console\Command;

class FirebaseTestPush extends Command
{
    protected $signature = 'firebase:test
                            {--user= : معرّف المستخدم المستلم}
                            {--token= : إرسال لتوكن جهاز محدد بدل مستخدم}
                            {--title=اختبار : عنوان الإشعار}
                            {--body=رسالة تجريبية من السيرفر : نص الإشعار}';

    protected $description = 'إرسال إشعار تجريبي عبر Firebase للتحقق من صحة الإعدادات';

    public function handle(FirebasePushService $push): int
    {
        $user = $this->option('user');
        $token = $this->option('token');

        if (!$user && !$token) {
            $this->error('يجب تمرير --user أو --token');

            return self::FAILURE;
        }

        $result = $token
            ? $push->sendToTokens([$token], $this->option('title'), $this->option('body'), ['type' => 'test'])
            : $push->sendToUser((int) $user, $this->option('title'), $this->option('body'), ['type' => 'test']);

        $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($result->wasSkipped()) {
            $this->warn('تم تخطي الإرسال — راجع السبب أعلاه.');

            return self::FAILURE;
        }

        $this->info("نجح: {$result->successCount()} | فشل: {$result->failureCount()}");

        return $result->successCount() > 0 ? self::SUCCESS : self::FAILURE;
    }
}
