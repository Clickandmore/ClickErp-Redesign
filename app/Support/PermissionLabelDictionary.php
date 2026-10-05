<?php

namespace App\Support;

/**
 * قاموس مسميات الصلاحيات.
 *
 * مولّد الأسماء السابق كان يترجم الفعل فقط (إضافة/عرض/تعديل/حذف) ويترك اسم
 * المورد بالإنجليزية، فظهرت في الشاشة مسميات مثل «إضافة الحساب statement».
 * هذا القاموس يترجم المورد كاملاً: جملة كاملة أولاً ثم كلمة كلمة.
 */
final class PermissionLabelDictionary
{
    /** الأفعال في نهاية اسم الصلاحية. الأطول أولاً حتى لا يلتقط الأقصر جزءاً منه. */
    private const ACTIONS = [
        'view_all_branches' => 'عرض كل الفروع',
        'force_delete' => 'حذف نهائي',
        'trashed_view' => 'عرض المحذوف',
        'change_status' => 'تغيير حالة',
        'toggle_status' => 'تفعيل أو تعطيل',
        'export_excel' => 'تصدير Excel',
        'export_pdf' => 'تصدير PDF',
        'create' => 'إضافة',
        'view' => 'عرض',
        'read' => 'عرض',
        'show' => 'عرض',
        'edit' => 'تعديل',
        'update' => 'تعديل',
        'delete' => 'حذف',
        'destroy' => 'حذف',
        'control' => 'إدارة',
        'manage' => 'إدارة',
        'assign' => 'تعيين',
        'activate' => 'تفعيل',
        'deactivate' => 'إلغاء تفعيل',
        'restore' => 'استعادة',
        'export' => 'تصدير',
        'import' => 'استيراد',
        'approve' => 'اعتماد',
        'reject' => 'رفض',
        'send' => 'إرسال',
        'print' => 'طباعة',
        'post' => 'اعتماد',
        'payout' => 'صرف',
        'reverse' => 'عكس',
        'cancel' => 'إلغاء',
        'confirm' => 'تأكيد',
        'reply' => 'الرد على',
        'response' => 'الرد على',
    ];

    /** مسميات كاملة لصلاحيات بعينها، تتجاوز أي تركيب آلي. */
    private const EXACT = [
        'company_settings_view' => 'عرض إعدادات الشركة',
        'company_settings_update' => 'تعديل بيانات الشركة والحساب والشعار',
        'company_settings_migration_preview' => 'معاينة ترحيل عقود الشركة',
        'company_settings_migration_execute' => 'تنفيذ ترحيل عقود الشركة وأرشفة العملاء',
        'company_settings_history_view' => 'عرض سجل ترحيل الشركة',
        'hourly_maids_impersonate' => 'الدخول إلى بوابة العاملة نيابة عنها',
        'hourly_customers_balance' => 'إيداع رصيد في محفظة عميل الساعات',
        'hourly_customers_balance_adjust' => 'تسوية رصيد محفظة عميل الساعات',
        'hourly_customers_merge' => 'دمج عملاء الساعات المكررين',
        'hourly_customers_notify' => 'إرسال إشعارات إلى عملاء الساعات',
        'hourly_drivers_impersonate' => 'الدخول إلى تطبيق السائق نيابة عنه',
        'hourly_orders_collect' => 'تحصيل مدفوعات طلبات الساعات ومراجعة التحويلات',
        'hourly_orders_notice_override' => 'تجاوز الحد الأدنى للمدة قبل موعد الحجز',
        'hourly_orders_price_override' => 'تعديل سعر طلب الساعات وتجاوز السعر المحدد',
        'hourly_orders_renew_again' => 'إعادة تجديد طلب ساعات سبق تجديده',
        'hourly_routing_capacity' => 'تعديل سعة فرق الساعات في خطة التوزيع',
        'hourly_routing_reorder' => 'إعادة ترتيب زيارات الساعات في المسار',
        'hourly_routing_transfer' => 'نقل زيارات الساعات بين الفرق',
        'complaints_read' => 'عرض الشكاوى',
        'complaint_response' => 'الرد على الشكاوى',
        'full_contract' => 'عرض العقد الكامل',
        'set_perms' => 'تعيين الصلاحيات للأدوار',
        'developers_perms' => 'إدارة صلاحيات المطور',
        'crm_module' => 'نظام إدارة العملاء (CRM)',
        'clients_view_all_branches' => 'عرض عملاء كل الفروع',
        'post_arrival_refund_view' => 'عرض مستندات الاسترداد بعد الوصول',
        'post_arrival_refund_create' => 'إنشاء مستند استرداد بعد الوصول',
        'post_arrival_refund_edit' => 'تعديل مسودة استرداد بعد الوصول',
        'post_arrival_refund_post' => 'اعتماد مستند استرداد بعد الوصول',
        'post_arrival_refund_payout' => 'صرف استرداد بعد الوصول',
        'post_arrival_refund_reverse' => 'عكس بند استرداد بعد الوصول',
    ];

    /**
     * أسماء موارد كاملة. تُستخدم قبل الترجمة كلمة كلمة لأن الترجمة الحرفية
     * تعطي معنى خاطئاً في هذه الحالات (مثل trail balance = ميزان المراجعة).
     */
    private const RESOURCES = [
        'hourly_customer_groups' => 'مجموعات عملاء الساعات',
        'hourly_customers' => 'عملاء التأجير بالساعة',
        'hourly_drivers' => 'سائقي التأجير بالساعة',
        'hourly_gps' => 'التتبع المباشر لمواقع فرق الساعات',
        'hourly_maids' => 'عاملات التأجير بالساعة',
        'hourly_orders' => 'طلبات التأجير بالساعة',
        'hourly_packages' => 'باقات التأجير بالساعة',
        'hourly_routing' => 'مسارات وتوزيع زيارات الساعات',
        'hourly_service_categories' => 'تصنيفات خدمات التأجير بالساعة',
        'hourly_teams' => 'فرق التأجير بالساعة',
        // مصطلحات مالية مركّبة أو مكتوبة بخطأ إملائي في اسم الصلاحية
        'finance_trail_balance' => 'ميزان المراجعة',
        'finance_catch_checks' => 'الشيكات المقبوضة',
        'finance_catch_checks_data' => 'بيانات الشيكات المقبوضة',
        'finance_tazher_check' => 'تظهير الشيكات',
        'finance_back_purchases' => 'مرتجعات المشتريات',
        'finance_back_sales' => 'مرتجعات المبيعات',
        'finance_qabd_bonds' => 'سندات القبض',
        'finance_sarf_bonds' => 'سندات الصرف',
        'finance_double_statement' => 'كشف الحساب المزدوج',
        'finance_account_statement' => 'كشف الحساب',
        'finance_account_statement_clients' => 'كشف حساب العملاء',
        'finance_account_statement_clients_print' => 'طباعة كشف حساب العملاء',
        'finance_account_cost_centers' => 'مراكز التكلفة',
        'finance_account_balance_differed_normal' => 'أرصدة الحسابات المؤجلة',
        'finance_load_blade_view' => 'قوالب الشاشات المالية (للمطور)',
        'finance_load_blade_edit_view' => 'تعديل قوالب الشاشات المالية (للمطور)',
        'finance_principle_movement' => 'حركة الأصول',
        'finance_principles' => 'الأصول الثابتة',
        'finance_principle' => 'الأصل الثابت',
        'finance_opening_constraint' => 'القيد الافتتاحي',
        'finance_daily_constraints' => 'القيود اليومية',
        'finance_manual_constraints' => 'القيود اليدوية',
        'finance_general_ledger' => 'دفتر الأستاذ العام',
        'finance_treasury_boxes' => 'الخزائن والصناديق',
        'finance_store_units' => 'وحدات المستودع',
        'finance_inventories' => 'الجرد',
        'finance_missing_data' => 'البيانات الناقصة',

        // موارد الموارد البشرية المكتوبة بخطأ أو باختصار
        'hr_mluti_employee' => 'الموظفين دفعة واحدة',
        'hr_employee_execel_file' => 'ملف الموظفين بصيغة Excel',
        'employee_my_complaints' => 'شكاواي',
        'employee_my_vacations' => 'إجازاتي',
        'employee_my_tasks' => 'مهامي',
        'employee_my_profile' => 'ملفي الشخصي',
        'employee_my_salaries' => 'رواتبي',
        'employee_my_advances' => 'سلفي',

        // شاشات تشغيلية
        'clients_view_all' => 'عملاء كل الفروع',
        'maids_view_all' => 'سير كل الفروع',
        'maids_change' => 'تغيير فرع السيرة الذاتية',
        'rec_contracts_escape_reports' => 'تقارير الهروب',
        'rec_contracts_escape_report' => 'تقرير الهروب',
        'rec_contracts_release_maid_after_completion' => 'إخلاء طرف العاملة بعد إتمام العقد',
        'invoice_credit_notes' => 'الإشعارات الدائنة للفواتير',
        'shelter_contract_rental_instant_receipt' => 'سند الاستلام الفوري لعقد التأجير',
        'sponsor_transfer_replace_maid' => 'استبدال العاملة في نقل الخدمات',
        'financial_requests' => 'الطلبات المالية',
        'financial_requests_close' => 'إغلاق الطلب المالي',
        'financial_requests_escalate' => 'تصعيد الطلب المالي',
        'financial_requests_request_closing' => 'طلب إغلاق الطلب المالي',
        'commissions' => 'العمولات',
        'commissions_calculate' => 'احتساب العمولات',
        'commissions_pay' => 'صرف العمولات',
        'commissions_settings' => 'إعدادات العمولات',
        'finance_bank_account_statement_print' => 'طباعة كشف الحساب البنكي',
        'finance_first_stocks' => 'أرصدة أول المدة',
        'finance_transfer_to_stock_movement' => 'حركة التحويل إلى المخزون',
        'finance_transfer_to_sarf_movement' => 'حركة التحويل إلى الصرف',
        'finance_item_sale_total' => 'إجمالي مبيعات الصنف',
        'finance_item_purchases_total' => 'إجمالي مشتريات الصنف',
        'finance_item_collects' => 'تحصيلات الصنف',

        'finance_get_code_by_category' => 'جلب الرمز حسب التصنيف',
        'finance_procurement_sarf' => 'صرف المشتريات',
        'admin_notifications' => 'إشعارات الموظفين',
        'find_us' => 'قسم أين تجدنا',
        'find' => 'قسم أين تجدنا',
        'why_choose_us' => 'قسم لماذا تختارنا',
        'why_choose' => 'قسم لماذا تختارنا',
        'recruitment_requirements' => 'متطلبات الاستقدام',
        'settings_cv_options' => 'خيارات السيرة الذاتية',
        'settings_cv_template' => 'قالب السيرة الذاتية',
        'users_force' => 'الحذف النهائي للمستخدمين',

        // إدارة علاقات العملاء
        'crm_my_followups' => 'متابعاتي',
        'crm_follow_clients' => 'متابعة العملاء',
        'crm_follow_clients_categories' => 'تصنيفات متابعة العملاء',
        'crm_confirm_clients' => 'اعتماد العملاء',
        'crm_print_price_offers' => 'طباعة عروض الأسعار',
        'crm_send_poll' => 'إرسال الاستبيانات',
        'crm_send_poll_user' => 'مستقبلو الاستبيانات',
        'crm_share_invoice' => 'مشاركة الفاتورة',
        'crm_social_contacts' => 'قنوات التواصل الاجتماعي',
    ];

    /** الترجمة كلمة كلمة عندما لا يوجد مورد كامل مطابق. */
    private const WORDS = [
        'accounting' => 'المحاسبة', 'add' => 'إضافة', 'addition' => 'الإضافة',
        'advances' => 'السلف', 'allowances' => 'البدلات', 'amount' => 'المبلغ',
        'analysis' => 'تحليل', 'and' => 'و', 'arithmetic' => 'الحسابية',
        'attendances' => 'الحضور والانصراف', 'audit' => 'المراجعة', 'available' => 'المتاح',
        'back' => 'المرتجع', 'balance' => 'الرصيد', 'balances' => 'الأرصدة',
        'bill' => 'الفاتورة', 'blade' => 'قالب الشاشة', 'bonds' => 'السندات',
        'boxes' => 'الصناديق', 'budget' => 'الموازنة', 'by' => 'حسب',
        'cash' => 'النقدية', 'catch' => 'القبض', 'center' => 'مركز',
        'centers' => 'مراكز', 'check' => 'الشيك', 'checks' => 'الشيكات',
        'child' => 'الفرعي', 'code' => 'الرمز', 'collects' => 'التحصيلات',
        'complaint' => 'الشكوى', 'complaints' => 'الشكاوى', 'confirm' => 'اعتماد',
        'constraint' => 'القيد', 'constraints' => 'القيود', 'contacts' => 'جهات الاتصال',
        'contract' => 'العقد', 'conversion' => 'التحويل', 'conversions' => 'التحويلات',
        'convert' => 'تحويل', 'cost' => 'التكلفة', 'creditor' => 'الدائن',
        'crm' => 'إدارة العملاء', 'daily' => 'اليومية', 'data' => 'البيانات',
        'date' => 'التاريخ', 'debtor' => 'المدين', 'declaration' => 'الإقرار',
        'departments' => 'الأقسام', 'deportation' => 'الترحيل', 'deported' => 'المُرحّلات',
        'depreciation' => 'الإهلاك', 'depreciations' => 'الإهلاكات', 'differed' => 'المؤجل',
        'double' => 'المزدوج', 'edit' => 'تعديل', 'excel' => 'Excel',
        'execel' => 'Excel', 'exchange' => 'الصرف', 'file' => 'الملف',
        'files' => 'الملفات', 'filter' => 'التصفية', 'first' => 'الأول',
        'follow' => 'متابعة', 'followups' => 'المتابعات', 'for' => 'لـ',
        'general' => 'العام', 'get' => 'جلب', 'group' => 'المجموعة',
        'guide' => 'الدليل', 'hr' => 'الموارد البشرية', 'import' => 'استيراد',
        'income' => 'الدخل', 'inside' => 'داخل', 'insurances' => 'التأمينات',
        'inventories' => 'الجرد', 'invoice' => 'الفاتورة', 'list' => 'القائمة',
        'load' => 'تحميل', 'management' => 'إدارة', 'manual' => 'اليدوي',
        'missing' => 'الناقص', 'mluti' => 'المتعدد', 'monthly' => 'الشهري',
        'movement' => 'الحركة', 'movements' => 'الحركات', 'my' => 'الخاصة بي',
        'normal' => 'العادي', 'num' => 'الرقم', 'official' => 'الرسمي',
        'opening' => 'الافتتاحي', 'order' => 'الطلب', 'orders' => 'الطلبات',
        'payment' => 'الدفع', 'pdf' => 'PDF', 'permissions' => 'الصلاحيات',
        'principle' => 'الأصل الثابت', 'principles' => 'الأصول الثابتة', 'print' => 'طباعة',
        'product' => 'المنتج', 'profile' => 'الملف الشخصي', 'purchase' => 'الشراء',
        'purchases' => 'المشتريات', 'qabd' => 'القبض', 'replies' => 'الردود',
        'return' => 'المرتجع', 'rewards' => 'المكافآت', 'role' => 'الدور',
        'salaries' => 'الرواتب', 'sale' => 'البيع', 'sales' => 'المبيعات',
        'sanctions' => 'الجزاءات', 'sarf' => 'الصرف', 'send' => 'إرسال',
        'share' => 'مشاركة', 'shelter' => 'الإيواء', 'shifts' => 'الورديات',
        'social' => 'التواصل الاجتماعي', 'stage' => 'المرحلة', 'statement' => 'كشف الحساب',
        'status' => 'الحالة', 'store' => 'المستودع', 'task' => 'المهمة',
        'tasks' => 'المهام', 'tax' => 'الضريبة', 'tazher' => 'التظهير',
        'to' => 'إلى', 'toggle' => 'تفعيل أو تعطيل', 'total' => 'الإجمالي',
        'trail' => 'المراجعة', 'transactions' => 'الحركات', 'treasury' => 'الخزينة',
        'types' => 'الأنواع', 'units' => 'الوحدات', 'user' => 'المستخدم',
        'vacation' => 'الإجازة', 'vacations' => 'الإجازات', 'view' => 'عرض',
        'websites' => 'المواقع', 'work' => 'العمل', 'year' => 'السنة',
        'report' => 'تقرير', 'reports' => 'التقارير', 'summary' => 'ملخص',
        'all' => 'كل', 'change' => 'تغيير', 'branch' => 'الفرع', 'branch_id' => 'الفرع',
        'rec' => 'الاستقدام', 'contracts' => 'العقود', 'escape' => 'الهروب',
        'release' => 'إخلاء طرف', 'maid' => 'العاملة', 'after' => 'بعد',
        'completion' => 'إتمام العقد', 'credit' => 'الدائنة', 'notes' => 'الإشعارات',
        'rental' => 'التأجير', 'instant' => 'الفوري', 'receipt' => 'سند الاستلام',
        'sponsor' => 'نقل الخدمات', 'transfer' => 'النقل', 'replace' => 'استبدال',
        'financial' => 'المالية', 'requests' => 'الطلبات', 'request' => 'الطلب',
        'close' => 'إغلاق', 'closing' => 'الإغلاق', 'escalate' => 'تصعيد',
        'commissions' => 'العمولات', 'calculate' => 'احتساب', 'pay' => 'صرف',
        'bank' => 'البنك', 'items' => 'الأصناف', 'item' => 'الصنف',
        'stocks' => 'المخزون', 'stock' => 'المخزون', 'warranty' => 'الضمان',
        'return' => 'الإرجاع', 'cancel' => 'الإلغاء', 'delegations' => 'التفاويض',
        'ingaz' => 'الإنجاز', 'musaned' => 'مساند', 'transfers' => 'التحويلات',
        'places' => 'المقرات', 'workers' => 'العمال', 'travel' => 'الرحلات',
        'agent' => 'الوكيل', 'recovery' => 'الاسترداد', 'quick' => 'السريعة',
        'tags' => 'الوسوم', 'desire' => 'الرغبة', 'remove' => 'إزالة',
        'from' => 'من', 'procedures' => 'الإجراءات', 'web' => 'الموقع',
        'poll' => 'الاستبيان', 'price' => 'السعر', 'offers' => 'العروض',
        'categories' => 'التصنيفات', 'followup' => 'المتابعة', 'log' => 'السجل',
        'activity' => 'النشاط', 'trashed' => 'المحذوف', 'languages' => 'اللغات',
        'sliders' => 'سلايدر الموقع', 'faq' => 'الأسئلة الشائعة', 'about' => 'من نحن',
        'us' => '', 'steps' => 'الخطوات', 'services' => 'الخدمات', 'content' => 'المحتوى',
        'packages' => 'الباقات', 'social_status' => 'الحالة الاجتماعية',
        'job' => 'المسمى', 'titles' => 'الوظيفي', 'statistics' => 'الإحصائيات',
        'category' => 'التصنيف', 'procurement' => 'المشتريات', 'admin' => 'الموظفين',
        'recruitment' => 'الاستقدام', 'requirements' => 'المتطلبات', 'why' => 'لماذا',
        'choose' => 'تختارنا', 'cv' => 'السيرة الذاتية', 'options' => 'خيارات',
        'template' => 'القالب', 'force' => 'نهائي', 'find' => 'أين تجدنا',
        'notification' => 'الإشعار', 'banks' => 'البنوك', 'setting' => 'الإعداد',
        'attachments' => 'المرفقات',

        'zatca' => 'هيئة الزكاة والضريبة والجمارك',

        // موارد شائعة كانت تُترجم سابقاً في الكنترولر
        'blogs' => 'المدونة', 'clients' => 'العملاء', 'employee' => 'الموظف',
        'employees' => 'الموظفين', 'finance' => 'النظام المالي', 'guidelines' => 'الإرشادات',
        'maids' => 'السير الذاتية', 'nationalities' => 'الجنسيات', 'offices' => 'المكاتب',
        'partners' => 'الشركاء', 'partenr' => 'الشركاء', 'relegions' => 'الأديان',
        'religions' => 'الأديان', 'roles' => 'الأدوار', 'skills' => 'المهارات',
        'stat' => 'الإحصائيات', 'users' => 'المستخدمين', 'branches' => 'الفروع',
        'countries' => 'الدول', 'accounts' => 'الحسابات', 'account' => 'الحساب',
        'ledger' => 'دفتر الأستاذ', 'suppliers' => 'الموردين', 'supplier' => 'المورد',
        'client' => 'العميل', 'notifications' => 'الإشعارات', 'settings' => 'الإعدادات',
    ];

    public static function actionLabel(string $action): ?string
    {
        return self::ACTIONS[$action] ?? null;
    }

    /** يفصل اسم الصلاحية إلى مورد وفعل. */
    public static function split(string $name): array
    {
        $normalized = self::normalize($name);

        foreach (self::ACTIONS as $suffix => $label) {
            if ($normalized === $suffix) {
                return ['resource' => '', 'action' => $suffix];
            }

            if (str_ends_with($normalized, '_' . $suffix)) {
                return [
                    'resource' => substr($normalized, 0, -strlen('_' . $suffix)),
                    'action' => $suffix,
                ];
            }
        }

        return ['resource' => $normalized, 'action' => ''];
    }

    /** ترجمة اسم المورد وحده، للاستخدام كعنوان صف في شاشة الصلاحيات. */
    public static function resourceLabel(string $resource): string
    {
        $resource = self::normalize($resource);

        if (str_starts_with($resource, 'hourly_') && isset(self::EXACT[$resource])) {
            return self::EXACT[$resource];
        }

        if (str_starts_with($resource, 'hourly_') && !isset(self::RESOURCES[$resource]) && ($page = HourlyPermissionCatalog::page($resource))) {
            return HourlyPermissionCatalog::PAGES[$page];
        }

        if ($resource === '') {
            return '';
        }

        if (isset(self::RESOURCES[$resource])) {
            return self::RESOURCES[$resource];
        }

        // جرّب إسقاط البادئة (finance_ / hr_ / crm_ / employee_) لأن الوحدة معروفة من القسم.
        foreach (['finance_', 'hr_', 'crm_', 'employee_', 'zatca_'] as $prefix) {
            if (str_starts_with($resource, $prefix)) {
                $stripped = substr($resource, strlen($prefix));

                if (isset(self::RESOURCES[$resource])) {
                    return self::RESOURCES[$resource];
                }

                if ($stripped !== '') {
                    return self::words($stripped);
                }
            }
        }

        return self::words($resource);
    }

    /** المسمى الكامل للصلاحية: الفعل + المورد. */
    public static function label(string $name): string
    {
        $normalized = self::normalize($name);

        if (isset(self::EXACT[$normalized])) {
            return self::EXACT[$normalized];
        }

        if (str_starts_with($normalized, 'hourly_') && !isset(self::RESOURCES[self::split($normalized)['resource']]) && ($label = HourlyPermissionCatalog::label($normalized))) {
            return $label;
        }

        ['resource' => $resource, 'action' => $action] = self::split($normalized);

        if ($action === '') {
            return self::resourceLabel($normalized);
        }

        $actionLabel = self::ACTIONS[$action];

        if ($resource === '') {
            return $actionLabel;
        }

        return trim($actionLabel . ' ' . self::resourceLabel($resource));
    }

    /** هل يحتاج المسمى الحالي إعادة توليد؟ (يحمل حروفاً لاتينية غير مسموح بها) */
    public static function needsTranslation(?string $displayName): bool
    {
        $displayName = trim((string) $displayName);

        if ($displayName === '') {
            return true;
        }

        $withoutAllowed = str_ireplace(['excel', 'pdf', 'crm', 'zatca'], '', $displayName);

        return (bool) preg_match('/[A-Za-z]/', $withoutAllowed);
    }

    private static function normalize(string $value): string
    {
        return trim(str_replace(['.', '-', ' '], '_', strtolower($value)), '_');
    }

    /** نعوت تأتي في الإنجليزية قبل الاسم وفي العربية بعده. */
    private const TRAILING_ADJECTIVES = [
        'monthly', 'daily', 'general', 'official', 'manual',
        'opening', 'normal', 'first', 'total', 'child', 'double', 'arithmetic',
    ];

    private static function words(string $resource): string
    {
        $parts = array_values(array_filter(explode('_', $resource), fn ($part) => $part !== ''));

        $adjectives = [];
        $nouns = [];

        foreach ($parts as $part) {
            if (in_array($part, self::TRAILING_ADJECTIVES, true) && count($parts) > 1) {
                $adjectives[] = self::WORDS[$part] ?? $part;
                continue;
            }

            $nouns[] = self::WORDS[$part] ?? $part;
        }

        return trim(implode(' ', array_merge($nouns, $adjectives)));
    }
}
