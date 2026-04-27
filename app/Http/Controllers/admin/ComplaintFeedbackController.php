<?php

namespace App\Http\Controllers\admin;

use App\Models\ComplaintFeedback;
use Illuminate\Http\Request;

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
        $message .= "بلاتنيوم الشرقية للاستقدام";

        // إرسال الرسالة عبر بوابة SMS
        self::sendSMS(formatSaudiPhone($complaint->complainant_phone), $message);

        return $feedback;
    }

    /**
     * إرسال SMS عبر البوابة
     */
    private static function sendSMS($phone, $message)
    {
        $ch = curl_init(config('msegat.url'));
        $data = array(
            'userName' => config('msegat.username'),
            'apiKey' => config('msegat.api_key'),
            'numbers' => $phone,
            "userSender" => config('msegat.sender'),
            'msg' => $message,
            'msgEncoding' => config('msegat.encoding'),
        );
        $response_data = json_encode($data);
        curl_setopt($ch, CURLOPT_POST, 1);
        //Attach our encoded JSON string to the POST fields.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $response_data);
        //Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        //Execute the request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        logger()->info("Msegat SMS Sent Successfully (Complaint Feedback): ", ['phone' => $phone, 'msg' => $msg, 'response' => $result]);

        return $result;
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
