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

    /** Explain an empty picker without confusing unsupported media with rejected templates. */
    public static function pickerNotice(array $snapshot, array $sendable): ?string
    {
        if (empty($snapshot['success'])) {
            return self::describe(['name' => '_diagnostic'], $snapshot)['detail'];
        }
        $unsupported = array_values(array_filter($snapshot['templates'] ?? [], fn ($row) =>
            strtoupper((string) ($row['status'] ?? '')) === 'APPROVED' && isset($row['sendable_by_api']) && !$row['sendable_by_api']));
        if ($unsupported) {
            $names = implode('، ', array_column(array_slice($unsupported, 0, 5), 'name'));
            return 'قوالب معتمدة موجودة في ClickChat لكنها غير مدعومة في شاشة إرسال ERP الحالية: '.$names
                .'. هذه الشاشة تدعم متغيرات النص فقط؛ القوالب ذات ترويسة صورة أو فيديو أو مستند أو متغيرات ترويسة تُرسل من صندوق ClickChat. أنشئ قالب خدمة نصياً لتجربة تحديثات العقود.';
        }
        return $sendable ? null : 'لا توجد قوالب معتمدة قابلة للإرسال لهذا الرقم حالياً. تحقق من الرقم واللغة وحالة المراجعة، ثم زامن القوالب من ميتا في ClickChat وحدّث الصفحة.';
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
                'auth_error' => 'مفتاح API مرفوض. حدّثه في إعدادات Click Live Chat.',
                'forbidden' => 'عرض الأرقام لا يكفي لقراءة القوالب. تحقق من صلاحية messages:write ومن صلاحية الحساب للوصول إلى القوالب.',
                'api_missing' => 'مسار API للقوالب غير موجود. تحقق من رابط المنصة ونشر تحديث ClickChat.',
                'invalid_sender' => 'تعذر قراءة قوالب الرقم المحدد. تحقق من ربط رقم ميتا بحساب الأعمال الصحيح.',
                'rate_limited' => 'وصلت طلبات قراءة القوالب إلى الحد المسموح. انتظر قليلاً ثم حدّث الحالة.',
                'api_outdated' => 'انشر تحديث API في ClickChat لعرض حالات القوالب غير المعتمدة أيضاً.',
            ];
            $labels = [
                'api_outdated' => 'يلزم تحديث ClickChat لقراءة الحالة',
                'auth_error' => 'مفتاح ClickChat مرفوض',
                'forbidden' => 'الوصول إلى القوالب غير مسموح',
                'api_missing' => 'API القوالب غير موجود',
                'sender_required' => 'رقم التحقق غير محدد',
                'invalid_sender' => 'تعذر قراءة قوالب هذا الرقم',
            ];
            return $result($error, $labels[$error] ?? 'حالة ميتا غير متاحة', 'secondary', $details[$error] ?? 'تعذر الاتصال أو قراءة القوالب. أعد التحديث وتحقق من صلاحيات مفتاح API.');
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
            'DELETED' => ['محذوف من ميتا', 'danger'],
        ];
        [$label, $color] = $labels[$status] ?? ['حالة ميتا غير معروفة', 'secondary'];
        $reason = $template['rejection_reason'] ?? null;
        $detail = match (true) {
            $status === 'REJECTED' && is_string($reason) && $reason !== '' => $reason,
            $status === 'DELETED' => 'القالب لم يعد موجودًا في ميتا ولن يُرسل. قدّمه من جديد بنفس الاسم أو اربط هذا القالب بقالب معتمد آخر.',
            default => 'الحالة المسجلة في ClickChat للرقم المستخدم في هذا الفرع. تتحدث بعد وصول إشعار ميتا أو مزامنة القوالب.',
        };
        return $result($status ?: 'unknown', $label, $color, $detail);
    }
}
