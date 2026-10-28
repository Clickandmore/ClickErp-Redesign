<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Models\McpAuditLog;
use App\Support\McpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * شاشة Super Admin لخادم MCP: التفعيل والإعدادات، إصدار التوكنات وإلغاؤها،
 * وآخر الاستدعاءات من سجل التدقيق.
 */
class SuperAdminMcpController extends Controller
{
    public function index()
    {
        $tablesReady = Schema::hasTable('mcp_access_tokens') && Schema::hasTable('mcp_audit_logs');

        return view('admin.superadmin.mcp', [
            'settings' => McpSettings::all(),
            'tablesReady' => $tablesReady,
            'endpoint' => url('/mcp'),
            'tokens' => $tablesReady
                ? McpAccessToken::query()->with('admin:id,name,email,branch_id,status')->latest('id')->limit(100)->get()
                : collect(),
            'logs' => $tablesReady
                ? McpAuditLog::query()->latest('id')->limit(50)->get()
                : collect(),
            'logAdmins' => $tablesReady
                ? Admin::withTrashed()->whereIn('id', McpAuditLog::query()->latest('id')->limit(50)->pluck('admin_id')->filter())
                    ->pluck('name', 'id')
                : collect(),
            'admins' => Admin::query()
                ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'suspended'))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'branch_id']),
            'plainToken' => session('mcp_plain_token'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'mask_sensitive' => ['nullable', 'boolean'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'token_days' => ['required', 'integer', 'min:0', 'max:3650'],
        ]);

        McpSettings::persist([
            'enabled' => $request->boolean('enabled'),
            'mask_sensitive' => $request->boolean('mask_sensitive'),
            'rate_limit_per_minute' => $validated['rate_limit_per_minute'],
            'token_days' => $validated['token_days'],
        ]);

        return redirect()->route('dev-superadmin.mcp.index')->with('success', 'تم حفظ إعدادات MCP.');
    }

    public function storeToken(Request $request)
    {
        $validated = $request->validate([
            'admin_id' => ['required', 'integer', 'exists:admins,id'],
            'name' => ['required', 'string', 'max:120'],
            'days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ], [
            'admin_id.required' => 'اختر الموظف الذي سيعمل المساعد بصلاحياته.',
            'name.required' => 'اكتب اسماً يميّز التوكن، مثل: Codex - لابتوب أحمد.',
        ]);

        $admin = Admin::query()->findOrFail($validated['admin_id']);

        if (($admin->status ?? 'active') === 'suspended') {
            return back()->withErrors(['admin_id' => 'هذا الموظف موقوف.'])->withInput();
        }

        $days = $validated['days'] ?? McpSettings::tokenDays();
        [$token, $plain] = McpAccessToken::issue($admin, $validated['name'], $days > 0 ? (int) $days : null);

        // يظهر مرة واحدة فقط في الطلب التالي؛ المخزَّن في القاعدة هو الـ hash.
        return redirect()->route('dev-superadmin.mcp.index')
            ->with('mcp_plain_token', ['id' => $token->id, 'value' => $plain, 'admin' => $admin->name])
            ->with('success', "تم إصدار التوكن #{$token->id} للموظف {$admin->name}.");
    }

    public function revokeToken(McpAccessToken $token)
    {
        if (!$token->revoked_at) {
            $token->forceFill(['revoked_at' => now()])->save();
        }

        return redirect()->route('dev-superadmin.mcp.index')->with('success', "تم إلغاء التوكن #{$token->id}.");
    }
}
