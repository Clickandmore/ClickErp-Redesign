<?php

namespace App\Mcp;

/**
 * قوالب جاهزة يختارها المستخدم من البرنامج المتصل، بدل أوامر إضافة Claude Code وحدها.
 * القالب يظهر فقط إذا كانت أداة يعتمد عليها متاحة للموظف.
 */
class McpPrompts
{
    public function listFor(array $availableTools): array
    {
        return collect($this->definitions())
            ->filter(fn (array $prompt) => array_intersect($prompt['tools'], $availableTools) !== [])
            ->map(fn (array $prompt, string $name) => [
                'name' => $name,
                'title' => $prompt['title'],
                'description' => $prompt['description'],
                'arguments' => $prompt['arguments'],
            ])
            ->values()
            ->all();
    }

    public function get(string $name, array $arguments, array $availableTools): ?array
    {
        $prompt = $this->definitions()[$name] ?? null;

        if (!$prompt || array_intersect($prompt['tools'], $availableTools) === []) {
            return null;
        }

        $text = $prompt['text'];
        foreach ($prompt['arguments'] as $argument) {
            $text = str_replace('{' . $argument['name'] . '}', trim((string) ($arguments[$argument['name']] ?? '')) ?: 'غير محدد', $text);
        }

        return [
            'description' => $prompt['description'],
            'messages' => [['role' => 'user', 'content' => ['type' => 'text', 'text' => $text]]],
        ];
    }

    private function definitions(): array
    {
        return [
            'morning_briefing' => [
                'title' => 'الملخص الصباحي',
                'description' => 'ما يحتاج تصرفاً اليوم، مرتباً حسب الأولوية.',
                'tools' => ['needs_attention'],
                'arguments' => [],
                'text' => 'استخدم needs_attention مع only_mine=true ثم بدونه إن كانت النتيجة فارغة. اعرض لي أهم ما يجب فعله اليوم مرتباً حسب الأولوية، مع روابط السجلات وعدد كل قسم.',
            ],
            'weekly_manager_report' => [
                'title' => 'تقرير المدير الأسبوعي',
                'description' => 'أداء الأسبوع الماضي مقارنة بما قبله.',
                'tools' => ['recruitment_contracts_report', 'rental_contracts_report', 'orders_report', 'invoices_summary'],
                'arguments' => [],
                'text' => 'أعد تقريراً تنفيذياً مختصراً للأسبوع الماضي: استخدم تقارير العقود المتاحة لي مع period=last_week وcompare_to=previous_period، والطلبات، والفواتير، وneeds_attention. ابدأ بثلاث نقاط تستحق قراراً، ولا تذكر رقماً لم يرجع من الأدوات.',
            ],
            'customer_overview' => [
                'title' => 'ملف عميل',
                'description' => 'كل ما يخص عميلاً: عقوده وفواتيره وشكاواه.',
                'tools' => ['get_customer'],
                'arguments' => [['name' => 'customer', 'description' => 'اسم العميل أو جواله', 'required' => true]],
                'text' => 'ابحث عن العميل «{customer}» بأداة search_customers، وإن تعددت النتائج فاسألني أيها أقصد. ثم استخدم get_customer ولخّص وضعه: العقود الجارية، المبالغ غير المدفوعة، الشكاوى المفتوحة، وما يجب فعله معه الآن.',
            ],
            'match_maid' => [
                'title' => 'مطابقة طلب عميل',
                'description' => 'عاملات متاحات يناسبن وصف العميل.',
                'tools' => ['search_maids'],
                'arguments' => [['name' => 'request', 'description' => 'وصف المطلوب، مثل: إثيوبية متاحة أقل من 35 سنة', 'required' => true]],
                'text' => 'حوّل هذا الطلب إلى فلاتر search_maids مع status=available: «{request}». اعرض أفضل النتائج في جدول مع الروابط، واذكر الإجمالي، وإن لم تجد نتائج فاقترح أي شرط يمكن تخفيفه.',
            ],
            'contract_status' => [
                'title' => 'حالة عقد',
                'description' => 'مرحلة العقد وفاتورته وضمانه وآخر ملاحظاته.',
                'tools' => ['get_recruitment_contract', 'get_rental_contract'],
                'arguments' => [['name' => 'contract', 'description' => 'رقم العقد أو اسم العميل', 'required' => true]],
                'text' => 'اعرض حالة العقد «{contract}»: حدّده بأدوات القوائم إن لزم، ثم استخدم أداة التفاصيل المناسبة. لخّص المرحلة الحالية والمبالغ والضمان وآخر الملاحظات والخطوة التالية.',
            ],
            'finance_summary' => [
                'title' => 'الملخص المالي',
                'description' => 'الفواتير والتحصيل والديون لفترة.',
                'tools' => ['invoices_summary', 'finance_bonds_summary', 'receivables_report'],
                'arguments' => [
                    ['name' => 'from', 'description' => 'من تاريخ YYYY-MM-DD', 'required' => false],
                    ['name' => 'to', 'description' => 'إلى تاريخ YYYY-MM-DD', 'required' => false],
                ],
                'text' => 'أعد ملخصاً مالياً من {from} إلى {to} (إن لم يُحدد التاريخ فهذا الشهر) بالأدوات المتاحة لي: invoices_summary وfinance_bonds_summary وreceivables_report وprofit_loss_summary. اذكر ما لم تتح لي صلاحيته بدل تقديره.',
            ],
            'message_customers' => [
                'title' => 'مراسلة عملاء',
                'description' => 'رسالة SMS أو واتساب لعميل واحد أو أكثر، بعد موافقتك.',
                'tools' => ['send_customer_message'],
                'arguments' => [
                    ['name' => 'customers', 'description' => 'أسماء العملاء أو وصفهم', 'required' => true],
                    ['name' => 'message', 'description' => 'ما تريد قوله', 'required' => true],
                ],
                'text' => 'حدّد هؤلاء العملاء بأداة search_customers: {customers}، واسألني عند أي تشابه في الأسماء. صغ رسالة قصيرة ومهذبة تقول: {message}، واسألني هل أريد SMS أم واتساب. ثم استدعِ send_customer_message وأعطني رابط الموافقة.',
            ],
            'activity_review' => [
                'title' => 'مراجعة النشاط',
                'description' => 'من فعل ماذا، والعمليات اللافتة.',
                'tools' => ['activity_log'],
                'arguments' => [['name' => 'scope', 'description' => 'فترة أو اسم موظف', 'required' => false]],
                'text' => 'راجع activity_log لهذا النطاق: {scope}. لخّص النشاط حسب الموظف ونوع العملية، وأبرز عمليات الحذف والتعديلات المالية وتغييرات الصلاحيات.',
            ],
        ];
    }
}
