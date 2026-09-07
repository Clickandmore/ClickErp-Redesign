<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ingaz\User;
use App\Models\ingaz\UserFirebaseToken;
use App\Services\Firebase\FirebasePushService;
use App\Support\FirebaseSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * صفحة التحكم بإشعارات Firebase: تفعيلها، رفع ملف حساب الخدمة، ضبط قيم
 * الأداء والمنصات، وإرسال إشعار تجريبي — كل ذلك دون لمس ملف .env.
 */
class FirebaseSettingsController extends Controller
{
    public function index()
    {
        return view('admin.firebase_settings.index', $this->pageData());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['nullable', 'string', 'max:255'],
            'timeout' => ['required', 'integer', 'min:3', 'max:120'],
            'concurrency' => ['required', 'integer', 'min:1', 'max:100'],
            'token_cache_ttl' => ['required', 'integer', 'min:60', 'max:3600'],
            'android_priority' => ['required', Rule::in(['high', 'normal'])],
            'android_channel_id' => ['required', 'string', 'max:100'],
            'android_sound' => ['required', 'string', 'max:100'],
            'apns_sound' => ['required', 'string', 'max:100'],
        ], [], [
            'project_id' => 'معرّف المشروع',
            'timeout' => 'مهلة الاتصال',
            'concurrency' => 'عدد الطلبات المتوازية',
            'token_cache_ttl' => 'مدة تخزين توكن المصادقة',
            'android_priority' => 'أولوية أندرويد',
            'android_channel_id' => 'قناة أندرويد',
            'android_sound' => 'صوت أندرويد',
            'apns_sound' => 'صوت iOS',
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['prune_invalid_tokens'] = $request->boolean('prune_invalid_tokens');

        FirebaseSettings::save($data);

        return redirect()
            ->route('dash.firebase-settings.index')
            ->with('success', 'تم حفظ إعدادات إشعارات Firebase بنجاح.');
    }

    /**
     * رفع ملف حساب الخدمة (Service Account JSON) وحفظه في المسار المُعدّ له.
     */
    public function uploadCredentials(Request $request)
    {
        $request->validate([
            'credentials' => ['required', 'file', 'max:64'],
        ], [
            'credentials.required' => 'اختر ملف حساب الخدمة أولاً.',
            'credentials.max' => 'حجم الملف أكبر من المتوقع لملف حساب خدمة.',
        ]);

        $contents = (string) file_get_contents($request->file('credentials')->getRealPath());
        $check = FirebaseSettings::validateCredentialsJson($contents);

        if (!$check['ok']) {
            return back()->withErrors(['credentials' => $check['error']]);
        }

        $path = FirebaseSettings::credentialsPath();
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return back()->withErrors(['credentials' => "تعذر إنشاء المجلد: {$directory}"]);
        }

        if (file_put_contents($path, $contents) === false) {
            return back()->withErrors(['credentials' => "تعذر حفظ الملف في: {$path}"]);
        }

        // الملف يحمل مفتاحاً خاصاً — يُقرأ من التطبيق فقط.
        @chmod($path, 0600);

        // توكن المصادقة المخزّن يخصّ حساب الخدمة السابق.
        FirebaseSettings::clearCache();
        $this->forgetAccessToken();

        return redirect()
            ->route('dash.firebase-settings.index')
            ->with('success', "تم رفع ملف حساب الخدمة بنجاح لمشروع: {$check['project_id']}");
    }

    public function deleteCredentials()
    {
        $path = FirebaseSettings::credentialsPath();

        if (is_file($path) && !@unlink($path)) {
            return back()->withErrors(['credentials' => 'تعذر حذف الملف، تحقق من صلاحيات المجلد.']);
        }

        $this->forgetAccessToken();

        return redirect()
            ->route('dash.firebase-settings.index')
            ->with('success', 'تم حذف ملف حساب الخدمة. الإشعارات ستتوقف حتى يُرفع ملف جديد.');
    }

    /**
     * إرسال إشعار تجريبي لمستخدم أو لتوكن جهاز محدد.
     */
    public function test(Request $request, FirebasePushService $push)
    {
        $data = $request->validate([
            'target_type' => ['required', Rule::in(['user', 'token'])],
            'user_id' => ['required_if:target_type,user', 'nullable', 'integer', 'exists:users,id'],
            'device_token' => ['required_if:target_type,token', 'nullable', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
        ], [
            'user_id.exists' => 'لا يوجد عميل بهذا المعرّف.',
            'user_id.required_if' => 'أدخل معرّف العميل.',
            'device_token.required_if' => 'أدخل توكن الجهاز.',
        ]);

        try {
            $result = $data['target_type'] === 'token'
                ? $push->sendToTokens([$data['device_token']], $data['title'], $data['body'], ['type' => 'test'])
                : $push->sendToUser((int) $data['user_id'], $data['title'], $data['body'], ['type' => 'test']);
        } catch (Throwable $e) {
            return back()->with('test_result', [
                'ok' => false,
                'summary' => 'فشل الإرسال: ' . $e->getMessage(),
                'details' => [],
            ]);
        }

        $summary = $result->wasSkipped()
            ? 'تم تخطي الإرسال — راجع التفاصيل أدناه.'
            : "نجح: {$result->successCount()} | فشل: {$result->failureCount()}";

        return back()->with('test_result', [
            'ok' => !$result->wasSkipped() && $result->successCount() > 0,
            'summary' => $summary,
            'details' => $result->toArray(),
        ]);
    }

    private function pageData(): array
    {
        return [
            'firebase' => FirebaseSettings::all(),
            'firebaseCredentials' => FirebaseSettings::credentialsStatus(),
            'tokensCount' => UserFirebaseToken::count(),
            'androidTokens' => UserFirebaseToken::where('software_type', 'android')->count(),
            'iosTokens' => UserFirebaseToken::where('software_type', 'ios')->count(),
            'usersWithTokens' => UserFirebaseToken::distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * توكن OAuth المخزّن مرتبط ببصمة حساب الخدمة، فيصبح بلا معنى عند تبديله.
     */
    private function forgetAccessToken(): void
    {
        try {
            \Illuminate\Support\Facades\Cache::forget(
                'firebase:access_token:' . \App\Services\Firebase\FirebaseCredentials::fromConfig()->fingerprint()
            );
        } catch (Throwable $e) {
            // لا يوجد ملف صالح ليُشتق منه مفتاح الكاش — لا شيء لنمسحه.
        }
    }
}
