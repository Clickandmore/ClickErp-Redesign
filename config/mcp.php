<?php

/*
 * خادم MCP الخاص بـ ClickERP — يتيح لمساعدات الذكاء الاصطناعي (Codex، Claude، ...)
 * قراءة بيانات النظام بصلاحيات موظف حقيقي. الشرح الكامل في docs/MCP_INTEGRATION.md.
 */
return [

    // مغلق افتراضياً: المسار /mcp يرجع 404 حتى يُفعَّل صراحةً على السيرفر.
    'enabled' => (bool) env('MCP_ENABLED', false),

    // الحد الأقصى للطلبات في الدقيقة لكل توكن.
    'rate_limit_per_minute' => (int) env('MCP_RATE_LIMIT', 60),

    // إخفاء أرقام الجواز والهوية والجوال (يظهر آخر 4 أرقام فقط) قبل إرسالها للمساعد.
    'mask_sensitive' => (bool) env('MCP_MASK_SENSITIVE', true),

    // مدة صلاحية التوكن الافتراضية بالأيام عند إصداره من أمر mcp:token.
    'token_days' => (int) env('MCP_TOKEN_DAYS', 90),

    'max_results' => 50,

    'tools' => [
        \App\Mcp\Tools\WhoAmITool::class,
        \App\Mcp\Tools\SearchMaidsTool::class,
        \App\Mcp\Tools\ListRecruitmentContractsTool::class,
        \App\Mcp\Tools\GetRecruitmentContractTool::class,
    ],
];
