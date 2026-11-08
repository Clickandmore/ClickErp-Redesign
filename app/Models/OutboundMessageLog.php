<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundMessageLog extends Model
{
    use \App\Models\Concerns\BranchScoped;

    protected $guarded = [];
    protected $casts = ['context' => 'array', 'provider_response' => 'array', 'accepted_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime', 'failed_at' => 'datetime'];

    public const CHANNELS = ['sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'email' => 'البريد الإلكتروني', 'notification' => 'داخل النظام', 'push' => 'إشعارات التطبيق'];
    public const STATUSES = ['pending' => 'قيد الإرسال', 'accepted' => 'قبله المزوّد', 'sent' => 'أرسله المزوّد', 'delivered' => 'تم التسليم', 'read' => 'تمت القراءة', 'failed' => 'فشل', 'skipped' => 'لم يُرسل', 'unknown' => 'سجل قديم — النتيجة غير مؤكدة'];

    public function actor() { return $this->belongsTo(\App\Models\auth\Admin::class, 'admin_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }

    public static function sourceLabel(?string $source): string
    {
        $labels = [
            'rental_contract_overdue_sms' => 'تذكير بتأخر عقد الإيجار',
            'rental_contract_expiry_response' => 'متابعة رد العميل على انتهاء الإيجار',
            'rental_payment_link' => 'رابط دفع عقد الإيجار',
            'maid_cv_share' => 'مشاركة السير الذاتية',
            'rental_contract_expiry_reminder' => 'تنبيه انتهاء عقد التأجير',
            'rental-contracts:send-expiry-reminders' => 'التنبيهات المجدولة لانتهاء عقود التأجير',
            'notifications:run-configured-alerts' => 'التنبيهات المجدولة',
            'configured_alert' => 'تنبيه آلي', 'notifications.store' => 'إرسال يدوي',
            'whatsapp.notifications.store' => 'إرسال واتساب يدوي', 'whatsapp_inbox' => 'صندوق واتساب',
            'phone_verification_code' => 'التحقق من الجوال', 'hourly_staff_login' => 'دخول تطبيق الطاقم',
            'legacy:communication_logs' => 'سجل المراسلات السابق', 'legacy:notifications' => 'سجل الرسائل السابق',
            'legacy:admin_notifications' => 'إشعارات الموظفين السابقة',
            'legacy:rental_contract_expiry_reminders' => 'تنبيهات انتهاء التأجير السابقة',
            'legacy:whatsapp_messages' => 'سجل صندوق واتساب السابق', 'system' => 'النظام',
            'recruitment_stage' => 'إشعارات مراحل الاستقدام',
            'recruitment:send-stage-notifications' => 'إشعارات مراحل الاستقدام',
        ];
        return $labels[$source ?? ''] ?? ($source ?: 'غير محدد');
    }
}
