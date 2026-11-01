<?php

namespace App\Http\Controllers;

use App\Mcp\McpAccess;
use App\Mcp\McpPrompts;
use App\Mcp\McpServer;
use App\Mcp\Tools\McpTool;
use App\Support\McpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * صفحة إرشادية لكل موظف ما دام ظهورها مفعّلاً من Super Admin: الرابط وخطوات الربط وما يستطيع سؤاله.
 * عرضها لا يفتح المساعد لأحد؛ تفعيل الموظف والصلاحيات يبقيان كما هما عند الربط والاستدعاء.
 * للعرض فقط؛ إدارة الموظفين والاتصالات تبقى لفريق ClickERP في شاشة Super Admin.
 */
class McpGuideController extends Controller
{
    public function show(Request $request, McpServer $server, McpAccess $access, McpPrompts $prompts)
    {
        abort_unless(McpSettings::guidePageEnabled(), 404);

        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.form.login');
        }

        $activated = $access->adminAllowed($admin);

        // غير المفعّل يرى أمثلة القراءة التي تسمح بها صلاحياته، لا أدوات الإضافة.
        $tools = collect($server->tools())->filter(fn (McpTool $tool) => $tool->isAllowedFor($admin) && ($activated
            ? $access->toolAllowed($admin, $tool)
            : McpSettings::toolEnabled($tool->name()) && !$tool->isWriteTool()));

        return view('admin.mcp.guide', [
            'admin' => $admin,
            'serverEnabled' => McpSettings::enabled(),
            'subscription' => \App\Support\McpSubscription::summary(),
            'activated' => $activated,
            'serverUrl' => McpOAuthController::absoluteUrl('/mcp'),
            'oauthEnabled' => McpSettings::oauthEnabled(),
            'prompts' => $prompts->listFor($tools->keys()->all()),
            'examples' => $this->examples($tools->keys()->all()),
        ]);
    }

    /** أمثلة بالعربية لما يملك الموظف صلاحيته فقط. */
    private function examples(array $tools): array
    {
        return collect([
            'needs_attention' => 'ماذا عليّ أن أتابع اليوم؟',
            'recruitment_contracts_report' => 'كم عقد استقدام في مرحلة مساند هذا الشهر مقارنة بالشهر الماضي؟',
            'rental_contracts_report' => 'كم عقد إيجار ينتهي خلال 30 يوماً؟',
            'search_maids' => 'أريد عاملة إثيوبية متاحة أقل من 35 سنة.',
            'get_customer' => 'لخّص لي وضع العميل محمد العتيبي.',
            'list_complaints' => 'ما الشكاوى العاجلة المفتوحة؟',
            'receivables_report' => 'من أكبر العملاء مديونية؟',
            'profit_loss_summary' => 'كم صافي الربح هذا الربع؟',
            'finance_bonds_summary' => 'كم حصّلنا هذا الأسبوع وكم صرفنا؟',
            'shelter_report' => 'كم عاملة في الإيواء جاهزة للاستلام؟',
            'create_order' => 'أنشئ طلب استقدام للعميل محمد العتيبي على هذه العاملة.',
            'send_customer_message' => 'أرسل واتساب لهؤلاء العملاء بأن عقودهم تنتهي هذا الأسبوع.',
        ])->only($tools)->values()->all();
    }
}
