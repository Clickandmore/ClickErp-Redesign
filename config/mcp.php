<?php

/*
 * خادم MCP الخاص بـ ClickERP — يتيح لمساعدات الذكاء الاصطناعي (Codex، Claude، ...)
 * قراءة بيانات النظام بصلاحيات موظف حقيقي. الشرح الكامل في docs/MCP_INTEGRATION.md.
 *
 * لا قيم من .env: كل الإعدادات تُدار من شاشة Super Admin ← MCP ← الإعدادات
 * (McpSettings). القيم هنا هي الافتراضي فقط قبل أول حفظ من الشاشة.
 */
return [

    // مغلق افتراضياً: المسار /mcp يرجع 404 حتى يُفعَّل من الشاشة.
    'enabled' => false,

    // الحد الأقصى للطلبات في الدقيقة لكل توكن.
    'rate_limit_per_minute' => 60,

    // إخفاء أرقام الجواز والهوية والجوال (يظهر آخر 4 أرقام فقط) قبل إرسالها للمساعد.
    'mask_sensitive' => true,

    // مدة صلاحية التوكن الافتراضية بالأيام عند إصداره.
    'token_days' => 90,

    'max_results' => 50,

    // selected: فقط الموظفون المضافون في شاشة Super Admin ← MCP ← الموظفون.
    // all: أي موظف لديه توكن صالح (عدا من أوقفته الشاشة صراحةً).
    'access_mode' => 'selected',

    // مفتاح عام لأدوات الإضافة والتعديل؛ مغلق افتراضياً حتى لو مُنح موظف مستوى «كتابة».
    'write_enabled' => false,

    // أقصى عدد عمليات إضافة عبر المساعد لكل موظف في الساعة.
    'write_limit_per_hour' => 30,

    // مدة الاحتفاظ بسجل الاستدعاءات بالأيام (0 = بلا حذف). يحذفه أمر mcp:prune اليومي.
    'audit_retention_days' => 180,

    'oauth' => [
        // ربط claude.ai وChatGPT وClaude Code بتسجيل دخول الموظف.
        'enabled' => false,

        // عمر الـ access token؛ العميل يجدده تلقائياً بالـ refresh token.
        'access_token_minutes' => 60,

        // عمر الـ refresh token عندما تكون "مدة التوكن" في الإعدادات 0 (بلا انتهاء).
        'refresh_token_max_days' => 365,

        // عناوين الرجوع المسموحة لعملاء OAuth. localhost و127.0.0.1 مسموحان دائماً
        // (Claude Code وClaude Desktop يستقبلان الرد على منفذ محلي).
        'redirect_hosts' => [
            'claude.ai',
            'claude.com',
            'chatgpt.com',
            'chat.openai.com',
        ],
    ],

    'tools' => [
        \App\Mcp\Tools\WhoAmITool::class,
        \App\Mcp\Tools\SearchMaidsTool::class,
        \App\Mcp\Tools\ListRecruitmentContractsTool::class,
        \App\Mcp\Tools\GetRecruitmentContractTool::class,
        \App\Mcp\Tools\ListRentalContractsTool::class,
        \App\Mcp\Tools\SearchCustomersTool::class,
        \App\Mcp\Tools\ListComplaintsTool::class,
        \App\Mcp\Tools\InvoicesSummaryTool::class,
        \App\Mcp\Tools\FinanceAccountBalanceTool::class,
        \App\Mcp\Tools\FinanceBondsSummaryTool::class,
        \App\Mcp\Tools\ActivityLogTool::class,

        // التقارير: أرقام مجمّعة لفترة، كل حساب في قاعدة البيانات.
        \App\Mcp\Tools\RecruitmentContractsReportTool::class,
        \App\Mcp\Tools\RentalContractsReportTool::class,
        \App\Mcp\Tools\SponsorshipTransfersReportTool::class,
        \App\Mcp\Tools\ShelterReportTool::class,
        \App\Mcp\Tools\OrdersReportTool::class,
        \App\Mcp\Tools\MaidsReportTool::class,
        \App\Mcp\Tools\CustomersReportTool::class,

        // الإضافة: معاينة ثم تأكيد (McpWriteTool). تحتاج مفتاح الكتابة ومستوى «قراءة وإضافة».
        \App\Mcp\Tools\FormOptionsTool::class,
        \App\Mcp\Tools\CreateCustomerTool::class,
        \App\Mcp\Tools\CreateMaidCvTool::class,
    ],
];
