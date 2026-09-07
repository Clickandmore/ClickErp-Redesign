<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use App\Models\Concerns\BranchScoped;

class ComplaintFeedback extends Model
{
    use AssignsBranch, HasFactory, BranchScoped;

    // "feedback" is uncountable, so Eloquent would infer `complaint_feedback`.
    protected $table = 'complaint_feedbacks';

    protected $fillable = [
        'complaint_id',
        'branch_id',
        'user_id',
        'token',
        'overall_rating',
        'response_speed',
        'solution_quality',
        'employee_professionalism',
        'communication_quality',
        'would_recommend',
        'comments',
        'phone',
        'sms_sent_at',
        'submitted_at',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'sms_sent_at' => 'datetime',
        'submitted_at' => 'datetime',
        'response_speed' => 'integer',
        'solution_quality' => 'integer',
        'employee_professionalism' => 'integer',
        'communication_quality' => 'integer',
        'branch_id' => 'integer',
    ];

    /**
     * العلاقة مع الشكوى
     */
    public function complaint()
    {
        return $this->belongsTo(\App\Models\ingaz\Complaint::class);
    }

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\ingaz\User::class);
    }

    /**
     * إنشاء رمز فريد للتقييم
     */
    public static function generateToken()
    {
        do {
            $token = Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * الحصول على نص التقييم العام
     */
    public function getOverallRatingTextAttribute()
    {
        $ratings = [
            'very_satisfied' => 'راضٍ جداً',
            'satisfied' => 'راضٍ',
            'neutral' => 'محايد',
            'dissatisfied' => 'غير راضٍ',
            'very_dissatisfied' => 'غير راضٍ تماماً'
        ];

        return $ratings[$this->overall_rating] ?? 'غير محدد';
    }

    /**
     * الحصول على لون التقييم
     */
    public function getOverallRatingColorAttribute()
    {
        $colors = [
            'very_satisfied' => 'success',
            'satisfied' => 'info',
            'neutral' => 'warning',
            'dissatisfied' => 'orange',
            'very_dissatisfied' => 'danger'
        ];

        return $colors[$this->overall_rating] ?? 'secondary';
    }

    /**
     * حساب متوسط التقييمات التفصيلية
     */
    public function getAverageRatingAttribute()
    {
        $ratings = array_filter([
            $this->response_speed,
            $this->solution_quality,
            $this->employee_professionalism,
            $this->communication_quality
        ]);

        if (count($ratings) == 0) {
            return null;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }

    /**
     * التحقق من إمكانية التقييم
     */
    public function canSubmitFeedback()
    {
        // التقييم متاح لمدة 30 يوم من إرسال الرسالة
        if ($this->submitted_at) {
            return false; // تم التقييم بالفعل
        }

        if (!$this->sms_sent_at) {
            return false; // لم يتم إرسال الرسالة بعد
        }

        $expiryDate = $this->sms_sent_at->addDays(30);
        return now()->lte($expiryDate);
    }

    /**
     * الحصول على رابط التقييم
     */
    public function getFeedbackUrlAttribute()
    {
        return route('complaint.feedback', ['token' => $this->token]);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromComplaint($this->complaint_id)
            ?? $this->resolveBranchIdFromUser($this->user_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
