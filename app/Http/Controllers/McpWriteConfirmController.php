<?php

namespace App\Http\Controllers;

use App\Mcp\McpAccess;
use App\Mcp\McpServer;
use App\Mcp\McpToolException;
use App\Mcp\PendingWrites;
use App\Mcp\Tools\McpWriteTool;
use App\Models\auth\Admin;
use App\Models\McpAuditLog;
use App\Support\BranchQueryContext;
use App\Support\DashboardMaintenanceSettings;
use App\Support\McpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * موافقة الموظف بنفسه على إضافة طلبها المساعد.
 *
 * الرابط يمر عبر النموذج، فلا يكفي وحده: التنفيذ يحتاج جلسة دخول صاحب الطلب في لوحة التحكم
 * وطلب POST بحماية CSRF، وهذا ما لا يستطيعه نموذج دفعه نص خبيث في شكوى أو ملاحظة.
 */
class McpWriteConfirmController extends Controller
{
    public function show(Request $request, string $token)
    {
        $admin = $this->adminOrNull();

        if (!$admin) {
            $request->session()->put(McpOAuthController::RETURN_SESSION_KEY, $request->fullUrl());

            return redirect()->route('admin.form.login');
        }

        [$pending, $tool] = $this->authorized($token, $admin);

        return view('admin.mcp.write-confirm', [
            'admin' => $admin,
            'tool' => $tool,
            'preview' => $pending['preview'] ?? [],
            'action' => route('mcp.write-confirm.store', ['token' => $token]),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $admin = $this->adminOrNull();
        abort_unless($admin, 403);

        [, $tool] = $this->authorized($token, $admin);

        try {
            $result = $tool->execute($token, $admin);
            $this->audit($admin, $tool, 'ok', null, $request->ip());
        } catch (McpToolException $e) {
            $this->audit($admin, $tool, 'error', $e->getMessage(), $request->ip());

            return view('admin.mcp.write-confirm', ['admin' => $admin, 'tool' => $tool, 'preview' => [], 'failure' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('MCP write confirm failed', ['tool' => $tool->name(), 'exception' => $e]);
            $this->audit($admin, $tool, 'error', $e->getMessage(), $request->ip());

            return view('admin.mcp.write-confirm', ['admin' => $admin, 'tool' => $tool, 'preview' => [], 'failure' => 'حدث خطأ داخلي ولم تكتمل العملية. راجع السجل في لوحة التحكم قبل إعادة المحاولة.']);
        }

        return view('admin.mcp.write-confirm', ['admin' => $admin, 'tool' => $tool, 'preview' => [], 'result' => $result]);
    }

    private function adminOrNull(): ?Admin
    {
        abort_unless(McpSettings::enabled(), 404);
        abort_if(DashboardMaintenanceSettings::enabled(), 503);

        $admin = Auth::guard('admin')->user();

        return $admin && ($admin->status ?? 'active') !== 'suspended' ? $admin : null;
    }

    /** الطلب لصاحبه فقط، وتُعاد فحوص الأداة لأن الصلاحية قد تكون سُحبت منذ المعاينة. */
    private function authorized(string $token, Admin $admin): array
    {
        abort_unless(\App\Support\McpSubscription::allowsWrite(), 403, \App\Support\McpSubscription::writeBlockedMessage());

        $pending = app(PendingWrites::class)->peek($token);

        abort_unless($pending && (int) $pending['admin_id'] === (int) $admin->id, 404, 'هذا الطلب غير موجود أو انتهت صلاحيته أو نُفذ من قبل. اطلب من المساعد معاينة جديدة.');

        $tool = app(McpServer::class)->tools()[$pending['tool']] ?? null;

        abort_unless(
            $tool instanceof McpWriteTool && app(McpAccess::class)->toolAllowed($admin, $tool) && $tool->isAllowedFor($admin),
            403,
            'لم تعد لديك صلاحية تنفيذ هذا الطلب.'
        );

        // نفس هوية الموظف في الشاشة: عزل الفروع وسجل النشاط باسمه.
        Auth::shouldUse('admin');
        BranchQueryContext::flush();

        return [$pending, $tool];
    }

    private function audit(Admin $admin, McpWriteTool $tool, string $status, ?string $error, ?string $ip): void
    {
        try {
            McpAuditLog::query()->create([
                'admin_id' => $admin->id,
                'method' => 'confirm',
                'tool' => $tool->name(),
                'status' => $status,
                'error' => $error !== null ? \Illuminate\Support\Str::limit($error, 497) : null,
                'ip' => $ip,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('MCP audit log failed', ['exception' => $e->getMessage()]);
        }
    }
}
