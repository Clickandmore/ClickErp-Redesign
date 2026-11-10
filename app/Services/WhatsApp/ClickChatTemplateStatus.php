<?php
namespace App\Services\WhatsApp;

class ClickChatTemplateStatus
{
    public function __construct(private readonly ClickChatWhatsAppService $clickChat) {}

    public function snapshot(): array
    {
        if (config('services.whatsapp.driver') !== 'clickchat') return ['success' => false, 'error' => 'other_provider'];
        return $this->clickChat->templateStatusSnapshot();
    }

    /** Derive a badge from one batch response; never infer rejection from absence. */
    public static function describe(?array $binding, array $snapshot): array
    {
        $result = fn ($state, $label, $color, $detail) => compact('state', 'label', 'color', 'detail');
        if (empty($binding['name'])) return $result('unbound', 'محلي - غير مرتبط بميتا', 'secondary',
            'حفظ قالب ERP لا ينشئ قالباً في ClickChat. قدّمه من صندوق واتساب ثم اربط اسمه ولغته هنا.');
        if (empty($snapshot['success'])) {
            $error = $snapshot['error'] ?? 'unavailable';
            $details = [
                'other_provider' => 'المزود الحالي مختلف؛ ربط Click Live Chat محفوظ ولا يؤثر على إرساله.',
                'not_configured' => 'أكمل رابط ClickChat ومفتاح API في إعدادات القنوات.',
                'sender_required' => 'حدد رقم ميتا الافتراضي أو رقم الفرع للتحقق من قوالبه.',
                'api_outdated' => 'انشر تحديث API في ClickChat لعرض حالات القوالب غير المعتمدة أيضاً.',
            ];
            return $result($error, 'حالة ميتا غير متاحة', 'secondary', $details[$error] ?? 'تعذر الاتصال أو قراءة القوالب. أعد التحديث وتحقق من صلاحيات مفتاح API.');
        }
        $matches = array_values(array_filter($snapshot['templates'] ?? [], fn ($row) =>
            ($row['name'] ?? '') === $binding['name'] && ($row['language'] ?? '') === ($binding['language'] ?? 'ar')));
        if (count($matches) !== 1) return $result('missing', 'غير موجود في ClickChat لهذا الرقم', 'warning text-dark',
            'تحقق من تقديم القالب، وتطابق الاسم واللغة وحساب واتساب ومساحة العمل. مزامنة ميتا تتم من ClickChat.');
        $template = $matches[0];
        $status = strtoupper((string) ($template['status'] ?? ''));
        $labels = [
            'APPROVED' => ['معتمد من ميتا', 'success'],
            'PENDING' => ['قيد مراجعة ميتا', 'warning text-dark'],
            'REJECTED' => ['مرفوض من ميتا', 'danger'],
            'PAUSED' => ['موقوف مؤقتاً لدى ميتا', 'warning text-dark'],
            'DISABLED' => ['معطّل لدى ميتا', 'danger'],
            'IN_APPEAL' => ['قيد الاستئناف', 'warning text-dark'],
            'PENDING_DELETION' => ['قيد الحذف', 'secondary'],
        ];
        [$label, $color] = $labels[$status] ?? ['حالة ميتا غير معروفة', 'secondary'];
        $reason = $template['rejection_reason'] ?? null;
        $detail = $status === 'REJECTED' && is_string($reason) && $reason !== '' ? $reason
            : 'الحالة المسجلة في ClickChat للرقم المستخدم في هذا الفرع. تتحدث بعد وصول إشعار ميتا أو مزامنة القوالب.';
        return $result($status ?: 'unknown', $label, $color, $detail);
    }
}
