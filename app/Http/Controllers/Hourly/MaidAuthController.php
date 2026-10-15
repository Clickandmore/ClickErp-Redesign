<?php
namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyMaidAccount;
use App\Services\Hourly\{StaffAppAuth, WorkerAppService};
use App\Support\HourlyStaffOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MaidAuthController extends Controller
{
    public function loginForm() { return view('hourly_maid.login'); }
    private function credentials(Request $request, string $channel): HourlyMaidAccount {
        $data=$request->validate(['login'=>['required','string','max:255'],'password'=>['required','string','max:72']]);
        $maid=HourlyMaidAccount::where('hourly_username',$data['login'])->orWhere('hourly_login_phone',$data['login'])->first();
        if (!$maid || !$maid->hourly_password || !Hash::check($data['password'],$maid->hourly_password) || !$maid->canLoginVia($channel)) {
            throw ValidationException::withMessages(['login'=>'بيانات الدخول غير صحيحة أو الحساب غير متاح على هذه المنصة.']);
        }
        return $maid;
    }
    public function webLogin(Request $request) {
        $maid=$this->credentials($request,'web');
        auth('hourly_maid')->login($maid); $request->session()->regenerate();
        $request->session()->put('hourly_maid_version',$maid->hourly_auth_version);
        return redirect()->route('hourly-maid.home');
    }
    /**
     * دخول الجوال بنمطين على نفس المسار: كلمة مرور (التوافق مع ما هو منشور)،
     * أو هاتف فقط فيُرسل رمز تحقق ويُكمل التطبيق بـ verify-otp.
     */
    public function mobileLogin(Request $request, StaffAppAuth $auth) {
        if (!$request->filled('password')) return $this->otpStart($request, $auth);
        $maid=$this->credentials($request,'mobile');
        return response()->json(['data'=>$auth->issue('hourly_maid_api',$maid,$this->remember($request))
            +['worker'=>app(WorkerAppService::class)->profile(\App\Models\ingaz\Maid::findOrFail($maid->id))]]);
    }

    /** «تذكّرني»: الافتراضي مفعّل لتطبيق الفريق؛ أرسل remember=false لجلسة قصيرة بمدة jwt.ttl. */
    private function remember(Request $request): bool
    {
        return $request->boolean('remember', true);
    }

    /** تجديد صامت بتوكن سارٍ. */
    public function refresh(Request $request, StaffAppAuth $auth)
    {
        $maid = \App\Models\ingaz\Maid::findOrFail(auth('hourly_maid_api')->id());
        return response()->json(['data' => $auth->refresh('hourly_maid_api', $this->remember($request))
            + ['worker' => app(WorkerAppService::class)->profile($maid)]]);
    }
    public function home(Request $request) { $data=$request->validate(['date'=>'nullable|date_format:Y-m-d']); return view('hourly_maid.home',['maid'=>auth('hourly_maid')->user(),'cashDate'=>$data['date'] ?? now()->toDateString()]); }
    public function me()
    {
        $maid = \App\Models\ingaz\Maid::findOrFail(auth('hourly_maid_api')->id());
        return response()->json(['data' => app(WorkerAppService::class)->profile($maid)]);
    }

    /** دخول التطبيق: الهاتف ثم رمز تحقق (الدخول بكلمة المرور أعلاه يبقى لبوابة الويب). */
    public function otpStart(Request $request, StaffAppAuth $auth)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $result = $auth->requestWorkerCode($data['phone']);
        return response()->json(['data' => array_filter($result, fn ($value) => $value !== null), 'message' => 'أرسلنا رمز التحقق إلى جوالك.']);
    }

    public function otpVerify(Request $request, StaffAppAuth $auth)
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20'], 'otp' => ['nullable', 'string', 'max:10'],
            'code' => ['nullable', 'string', 'max:10'], 'remember' => ['nullable', 'boolean']]);
        $code = (string) ($data['otp'] ?? $data['code'] ?? '');
        abort_if($code === '', 422, 'أدخل رمز التحقق.');
        return response()->json(['data' => $auth->verifyWorker($data['phone'], $code, $this->remember($request))]);
    }

    public function devices(Request $request, StaffAppAuth $auth)
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:500'], 'platform' => ['nullable', 'in:android,ios'], 'app_version' => ['nullable', 'string', 'max:30']]);
        return response()->json(['data' => $auth->device(HourlyStaffOtp::WORKER, (int) auth('hourly_maid_api')->id(), $data)]);
    }
    public function webLogout(Request $request) {
        if ($request->session()->pull('hourly_maid_impersonator')) {
            auth('hourly_maid')->logout();
            $request->session()->forget('hourly_maid_version');
            return redirect()->route('admin.hourly-maids.index');
        }
        auth('hourly_maid')->logout(); $request->session()->forget('hourly_maid_version');
        $request->session()->regenerate(); $request->session()->regenerateToken();
        return redirect()->route('hourly-maid.login');
    }
    public function mobileLogout() { auth('hourly_maid_api')->logout(); return response()->json(['message'=>'تم تسجيل الخروج.']); }
}
