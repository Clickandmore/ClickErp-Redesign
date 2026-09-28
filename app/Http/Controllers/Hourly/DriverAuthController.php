<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyDriver;
use App\Services\Hourly\StaffAppAuth;
use App\Support\HourlyStaffOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    public function loginForm() { return view('hourly_driver.login'); }

    private function credentials(Request $request, string $channel): HourlyDriver
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:255'], 'password' => ['required', 'string', 'max:72']]);
        // Generated usernames contain a prefix, so they cannot collide with phone identifiers.
        $driver = HourlyDriver::where('username', $data['login'])->orWhere('login_phone', $data['login'])->first();
        if (!$driver || !$driver->password || !Hash::check($data['password'], $driver->password) || !$driver->canLoginVia($channel)) {
            throw ValidationException::withMessages(['login' => 'بيانات الدخول غير صحيحة أو الحساب غير متاح على هذه المنصة.']);
        }
        return $driver;
    }

    public function webLogin(Request $request)
    {
        $driver = $this->credentials($request, 'web');
        auth('hourly_driver')->login($driver);
        $request->session()->regenerate();
        $request->session()->put('hourly_driver_version', $driver->auth_version);
        $request->session()->forget('hourly_driver_impersonator');
        return redirect()->route('hourly-driver.home');
    }

    /**
     * دخول الجوال بنمطين على نفس المسار: كلمة مرور (التوافق مع ما هو منشور)،
     * أو هاتف فقط فيُرسل رمز تحقق ويُكمل التطبيق بـ verify-otp.
     */
    public function mobileLogin(Request $request, StaffAppAuth $auth)
    {
        if (!$request->filled('password')) return $this->otpStart($request, $auth);
        $driver = $this->credentials($request, 'mobile');
        return response()->json(['data' => $auth->issue('hourly_driver_api', $driver, $this->remember($request))
            + ['driver' => $auth->driverPayload($driver)]]);
    }

    /** «تذكّرني»: الافتراضي مفعّل لتطبيق الفريق؛ أرسل remember=false لجلسة قصيرة بمدة jwt.ttl. */
    private function remember(Request $request): bool
    {
        return $request->boolean('remember', true);
    }

    /** تجديد صامت بتوكن سارٍ، فلا يُطالب السائق ببياناته مرة أخرى. */
    public function refresh(Request $request, StaffAppAuth $auth)
    {
        $driver = auth('hourly_driver_api')->user();
        return response()->json(['data' => $auth->refresh('hourly_driver_api', $this->remember($request))
            + ['driver' => $auth->driverPayload($driver)]]);
    }

    public function me()
    {
        $driver = auth('hourly_driver_api')->user();
        return response()->json(['data' => app(StaffAppAuth::class)->driverPayload($driver) + ['work_schedule' => $driver->work_schedule]]);
    }

    /** دخول التطبيق: الهاتف ثم رمز تحقق (الدخول بكلمة المرور أعلاه يبقى لبوابة الويب). */
    public function otpStart(Request $request, StaffAppAuth $auth)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $result = $auth->requestDriverCode($data['phone']);
        return response()->json(['data' => array_filter($result, fn ($value) => $value !== null), 'message' => 'أرسلنا رمز التحقق إلى جوالك.']);
    }

    public function otpVerify(Request $request, StaffAppAuth $auth)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20'], 'otp' => ['nullable', 'string', 'max:10'],
            'code' => ['nullable', 'string', 'max:10'], 'remember' => ['nullable', 'boolean']]);
        $code = (string) ($data['otp'] ?? $data['code'] ?? '');
        abort_if($code === '', 422, 'أدخل رمز التحقق.');
        return response()->json(['data' => $auth->verifyDriver($data['phone'], $code, $this->remember($request))]);
    }

    public function devices(Request $request, StaffAppAuth $auth)
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:500'], 'platform' => ['nullable', 'in:android,ios'], 'app_version' => ['nullable', 'string', 'max:30']]);
        return response()->json(['data' => $auth->device(HourlyStaffOtp::DRIVER, (int) auth('hourly_driver_api')->id(), $data)]);
    }

    public function webLogout(Request $request)
    {
        // جلسة فتحها مشرف من جدول السائقين: نُخرج السائق فقط ونعيده للوحة، دون تجديد الجلسة
        // أو رمز CSRF حتى لا تتعطل نماذج اللوحة المفتوحة في تبويبات المشرف الأخرى.
        if ($request->session()->pull('hourly_driver_impersonator')) {
            auth('hourly_driver')->logout();
            $request->session()->forget('hourly_driver_version');
            return redirect()->route('admin.hourly-drivers.index');
        }
        auth('hourly_driver')->logout();
        $request->session()->forget('hourly_driver_version');
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        return redirect()->route('hourly-driver.login');
    }

    public function mobileLogout()
    {
        auth('hourly_driver_api')->logout();
        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }
}
