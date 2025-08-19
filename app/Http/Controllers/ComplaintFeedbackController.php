<?php

namespace App\Http\Controllers;

use App\Models\ComplaintFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ComplaintFeedbackController extends Controller
{
    /**
     * عرض صفحة التقييم
     */
    public function show($token)
    {
        $feedback = ComplaintFeedback::where('token', $token)
            ->with('complaint')
            ->firstOrFail();

        return view('public.complaint-feedback', compact('feedback'));
    }

    /**
     * حفظ التقييم
     */
    public function submit(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:complaint_feedbacks,token',
            'overall_rating' => 'required|in:very_satisfied,satisfied,neutral,dissatisfied,very_dissatisfied',
            'response_speed' => 'nullable|integer|min:1|max:5',
            'solution_quality' => 'nullable|integer|min:1|max:5',
            'employee_professionalism' => 'nullable|integer|min:1|max:5',
            'communication_quality' => 'nullable|integer|min:1|max:5',
            'would_recommend' => 'nullable|in:yes,maybe,no',
            'comments' => 'nullable|string|max:1000'
        ]);

        $feedback = ComplaintFeedback::where('token', $request->token)->firstOrFail();

        // التحقق من إمكانية التقييم
        if (!$feedback->canSubmitFeedback()) {
            return redirect()->route('complaint.feedback', $request->token)
                ->with('error', 'عذراً، لا يمكن إرسال التقييم');
        }

        // حفظ التقييم
        $feedback->update([
            'overall_rating' => $request->overall_rating,
            'response_speed' => $request->response_speed,
            'solution_quality' => $request->solution_quality,
            'employee_professionalism' => $request->employee_professionalism,
            'communication_quality' => $request->communication_quality,
            'would_recommend' => $request->would_recommend,
            'comments' => $request->comments,
            'submitted_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return view('public.complaint-feedback-success', compact('feedback'));
    }

    /**
     * إرسال رسالة SMS للتقييم
     */
    public static function sendFeedbackSMS($complaint)
    {
        // إنشاء سجل تقييم جديد
        $feedback = ComplaintFeedback::create([
            'complaint_id' => $complaint->id,
            'user_id' => $complaint->user_id,
            'token' => ComplaintFeedback::generateToken(),
            'phone' => $complaint->complainant_phone,
            'sms_sent_at' => now()
        ]);

        // إعداد رسالة SMS
        $message = "عزيزنا العميل،\n";
        $message .= "تم حل شكواك رقم #" . str_pad($complaint->id, 6, '0', STR_PAD_LEFT) . "\n";
        $message .= "نود معرفة رأيك في جودة الخدمة المقدمة.\n";
        $message .= "للتقييم: " . $feedback->feedback_url . "\n";
        $message .= "شكراً لثقتك بنا.\n";
        $message .= "أسس الإنجاز للاستقدام";

        // إرسال الرسالة عبر بوابة SMS
        self::sendSMS($complaint->complainant_phone, $message);

        return $feedback;
    }

    /**
     * إرسال SMS عبر البوابة
     */
    private static function sendSMS($phone, $message)
    {
        // يمكنك استخدام أي بوابة SMS مثل Twilio, Unifonic, etc.
        // هذا مثال باستخدام Unifonic

        $apiKey = env('SMS_API_KEY');
        $senderId = env('SMS_SENDER_ID', 'ASSUS');

        if (!$apiKey) {
            \Log::warning('SMS API key not configured');
            return false;
        }

        try {
            $response = Http::post('https://el.cloud.unifonic.com/rest/SMS/messages', [
                'AppSid' => $apiKey,
                'SenderID' => $senderId,
                'Recipient' => $phone,
                'Body' => $message,
                'encoding' => 'UTF8'
            ]);

            if ($response->successful()) {
                \Log::info('SMS sent successfully to ' . $phone);
                return true;
            } else {
                \Log::error('Failed to send SMS: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('SMS sending error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * عرض التقييمات في لوحة التحكم
     */
    public function index(Request $request)
    {
        $feedbacks = ComplaintFeedback::with(['complaint', 'user'])
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at', 'desc');

        // الفلاتر
        if ($request->rating) {
            $feedbacks->where('overall_rating', $request->rating);
        }

        if ($request->from_date) {
            $feedbacks->whereDate('submitted_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $feedbacks->whereDate('submitted_at', '<=', $request->to_date);
        }

        $feedbacks = $feedbacks->paginate(20);

        // حساب الإحصائيات
        $stats = [
            'total' => ComplaintFeedback::whereNotNull('submitted_at')->count(),
            'very_satisfied' => ComplaintFeedback::where('overall_rating', 'very_satisfied')->count(),
            'satisfied' => ComplaintFeedback::where('overall_rating', 'satisfied')->count(),
            'neutral' => ComplaintFeedback::where('overall_rating', 'neutral')->count(),
            'dissatisfied' => ComplaintFeedback::where('overall_rating', 'dissatisfied')->count(),
            'very_dissatisfied' => ComplaintFeedback::where('overall_rating', 'very_dissatisfied')->count(),
            'average_rating' => ComplaintFeedback::whereNotNull('submitted_at')
                ->selectRaw('AVG((response_speed + solution_quality + employee_professionalism + communication_quality) / 4) as avg')
                ->first()->avg ?? 0
        ];

        return view('admin.ingaz.complaints.feedbacks', compact('feedbacks', 'stats'));
    }
}
