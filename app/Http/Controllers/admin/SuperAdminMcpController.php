<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Mcp\McpServer;
use App\Mcp\Tools\McpTool;
use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\McpAuditLog;
use App\Models\McpOAuthClient;
use App\Support\McpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * شاشة Super Admin لخادم MCP، مقسمة إلى تبويبات: نظرة عامة، الموظفون، الأدوات،
 * الاتصالات، السجل، الإعدادات، طريقة الربط. كل تبويب يحمّل بياناته فقط.
 */
class SuperAdminMcpController extends Controller
{
    public const TABS = [
        'overview' => 'نظرة عامة',
        'employees' => 'الموظفون',
        'tools' => 'الأدوات',
        'connections' => 'الاتصالات',
        'logs' => 'السجل',
        'settings' => 'الإعدادات',
        'connect' => 'طريقة الربط',
    ];

    public const CATEGORIES = [
        'contracts' => 'العقود',
        'customers' => 'العملاء',
        'maids' => 'السير الذاتية',
        'orders' => 'الطلبات',
        'complaints' => 'الشكاوى',
        'finance' => 'المالية',
        'system' => 'النظام',
    ];

    public function index(Request $request, McpServer $server)
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? (string) $request->query('tab') : 'overview';
        $tablesReady = $this->tablesReady();
        $settings = McpSettings::all();

        $data = [
            'tab' => $tab,
            'tabs' => self::TABS,
            'settings' => $settings,
            'tablesReady' => $tablesReady,
            'endpoint' => \App\Http\Controllers\McpOAuthController::absoluteUrl('/mcp'),
            'plainToken' => session('mcp_plain_token'),
            'tools' => collect($server->tools()),
            'categories' => self::CATEGORIES,
            'statusLabels' => McpAuditLog::STATUS_LABELS,
            'summary' => $tablesReady ? $this->summary() : ['connections' => 0, 'employees' => 0, 'calls_today' => 0],
        ];

        if ($tablesReady) {
            $data += match ($tab) {
                'overview' => $this->overviewData($settings, $data['tools']),
                'employees' => $this->employeesData(),
                'tools' => ['toolCalls' => $this->callsByTool(30)],
                'connections' => $this->connectionsData(),
                'logs' => $this->logsData($request, $data['tools']),
                default => [],
            };
        }

        return view('admin.superadmin.mcp', $data);
    }

    // ------------------------------------------------------------------ الإعدادات والأدوات

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'oauth_enabled' => ['nullable', 'boolean'],
            'mask_sensitive' => ['nullable', 'boolean'],
            'access_mode' => ['nullable', Rule::in([McpSettings::ACCESS_SELECTED, McpSettings::ACCESS_ALL])],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'token_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'audit_retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'write_limit_per_hour' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        McpSettings::persist(array_filter([
            'enabled' => $request->boolean('enabled'),
            'oauth_enabled' => $request->boolean('oauth_enabled'),
            'mask_sensitive' => $request->boolean('mask_sensitive'),
            'access_mode' => $validated['access_mode'] ?? null,
            'rate_limit_per_minute' => $validated['rate_limit_per_minute'],
            'token_days' => $validated['token_days'],
            'audit_retention_days' => $validated['audit_retention_days'] ?? null,
            'write_limit_per_hour' => $validated['write_limit_per_hour'] ?? null,
        ], fn ($value) => $value !== null));

        return $this->back('settings', 'تم حفظ إعدادات MCP.');
    }

    public function updateTools(Request $request, McpServer $server)
    {
        $names = array_keys($server->tools());
        $request->validate([
            'enabled_tools' => ['nullable', 'array'],
            'enabled_tools.*' => ['string', Rule::in($names)],
            'write_enabled' => ['nullable', 'boolean'],
        ]);

        McpSettings::persist([
            'disabled_tools' => array_values(array_diff($names, (array) $request->input('enabled_tools', []))),
            'write_enabled' => $request->boolean('write_enabled'),
        ]);

        return $this->back('tools', 'تم حفظ إعدادات الأدوات.');
    }

    // ------------------------------------------------------------------ الموظفون

    public function storeAdmin(Request $request)
    {
        $validated = $request->validate([
            'admin_id' => ['required', 'integer', 'exists:admins,id', Rule::unique('mcp_admin_access', 'admin_id')],
            'access_level' => ['required', Rule::in(McpAdminAccess::LEVELS)],
            'note' => ['nullable', 'string', 'max:190'],
        ], [
            'admin_id.required' => 'اختر الموظف.',
            'admin_id.unique' => 'هذا الموظف مضاف بالفعل.',
        ]);

        $access = McpAdminAccess::query()->create($validated + ['enabled' => true]);

        return $this->back('employees', 'تمت إضافة ' . ($access->admin->name ?? 'الموظف') . '.');
    }

    public function updateAdmin(Request $request, McpServer $server, McpAdminAccess $access)
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'access_level' => ['required', Rule::in(McpAdminAccess::LEVELS)],
            'tools_mode' => ['required', Rule::in(['all', 'selected'])],
            'allowed_tools' => ['nullable', 'array'],
            'allowed_tools.*' => ['string', Rule::in(array_keys($server->tools()))],
            'note' => ['nullable', 'string', 'max:190'],
        ]);

        $access->update([
            'enabled' => $request->boolean('enabled'),
            'access_level' => $validated['access_level'],
            'allowed_tools' => $validated['tools_mode'] === 'all' ? null : array_values($validated['allowed_tools'] ?? []),
            'note' => $validated['note'] ?? null,
        ]);

        return $this->back('employees', 'تم تحديث صلاحية ' . ($access->admin->name ?? 'الموظف') . '.');
    }

    public function destroyAdmin(McpAdminAccess $access)
    {
        $name = $access->admin->name ?? 'الموظف';
        $access->delete();

        $note = McpSettings::accessMode() === McpSettings::ACCESS_SELECTED
            ? ' لن يستطيع استخدام المساعد حتى تضيفه مجدداً.'
            : ' في وضع «الجميع» يبقى قادراً على الاستخدام للقراءة؛ أوقفه بدل حذفه لمنعه.';

        return $this->back('employees', "تمت إزالة {$name}." . $note);
    }

    public function revokeAdminConnections(Admin $admin)
    {
        $count = McpAccessToken::revokeAllFor((int) $admin->id);

        return $this->back($this->returnTab(request(), 'employees'), "تم قطع {$count} اتصال للموظف {$admin->name}.");
    }

    // ------------------------------------------------------------------ الاتصالات

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

        // إصدار توكن لموظف يعني الموافقة على استخدامه؛ يُضاف للقراءة إن لم يكن في القائمة.
        $access = McpAdminAccess::query()->firstOrCreate(['admin_id' => $admin->id], [
            'enabled' => true,
            'access_level' => McpAdminAccess::LEVEL_READ,
            'note' => 'أُضيف عند إصدار توكن',
        ]);
        $message = "تم إصدار التوكن #{$token->id} للموظف {$admin->name}.";
        if (!$access->enabled) {
            $message .= ' تنبيه: الموظف موقوف في تبويب الموظفين، فلن يعمل التوكن حتى تفعّله.';
        }

        // يظهر مرة واحدة فقط في الطلب التالي؛ المخزَّن في القاعدة هو الـ hash.
        return redirect()->route('dev-superadmin.mcp.index', ['tab' => 'connections'])
            ->with('mcp_plain_token', ['id' => $token->id, 'value' => $plain, 'admin' => $admin->name])
            ->with('success', $message);
    }

    public function revokeToken(McpAccessToken $token)
    {
        if (!$token->revoked_at) {
            $token->forceFill(['revoked_at' => now()])->save();
        }

        return $this->back('connections', "تم إلغاء التوكن #{$token->id}.");
    }

    public function revokeClient(McpOAuthClient $client)
    {
        $client->forceFill(['revoked_at' => now()])->save();
        $count = McpAccessToken::query()->where('oauth_client_id', $client->id)->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);

        return $this->back('connections', 'تم إلغاء التطبيق ' . ($client->client_name ?: $client->client_id) . " وقطع {$count} اتصال.");
    }

    // ------------------------------------------------------------------ السجل

    public function exportLogs(Request $request)
    {
        abort_unless($this->tablesReady(), 404);

        $query = $this->filteredLogs($request);
        $admins = Admin::withTrashed()->whereIn('id', (clone $query)->distinct()->pluck('admin_id')->filter())->pluck('name', 'id');

        return response()->streamDownload(function () use ($query, $admins) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['#', 'الوقت', 'الموظف', 'الطلب', 'الأداة', 'المدخلات', 'النتيجة', 'الخطأ', 'المدة (ms)', 'IP']);

            $query->orderByDesc('id')->chunk(1000, function ($logs) use ($out, $admins) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->id,
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $admins[$log->admin_id] ?? $log->admin_id,
                        $log->method,
                        $log->tool,
                        json_encode($log->arguments ?: new \stdClass(), JSON_UNESCAPED_UNICODE),
                        McpAuditLog::STATUS_LABELS[$log->status] ?? $log->status,
                        $log->error,
                        $log->duration_ms,
                        $log->ip,
                    ]);
                }
            });

            fclose($out);
        }, 'mcp-log-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------------ بيانات التبويبات

    private function summary(): array
    {
        $employees = McpAdminAccess::query()->where('enabled', true)->count();

        return [
            'connections' => $this->activeTokens()->count(),
            'employees' => McpSettings::accessMode() === McpSettings::ACCESS_ALL ? null : $employees,
            'calls_today' => McpAuditLog::query()->where('method', 'tools/call')->where('created_at', '>=', today())->count(),
        ];
    }

    private function overviewData(array $settings, Collection $tools): array
    {
        $today = McpAuditLog::query()->where('created_at', '>=', today())
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $daily = McpAuditLog::query()
            ->where('method', 'tools/call')
            ->where('created_at', '>=', today()->subDays(13))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = collect(range(13, 0))->map(function ($ago) use ($daily) {
            $date = today()->subDays($ago);

            return ['date' => $date, 'total' => (int) ($daily[$date->toDateString()] ?? 0)];
        });

        $topAdmins = McpAuditLog::query()
            ->where('method', 'tools/call')->where('created_at', '>=', now()->subDays(30))->whereNotNull('admin_id')
            ->selectRaw('admin_id, COUNT(*) as total')->groupBy('admin_id')->orderByDesc('total')->limit(5)->get();
        $adminNames = Admin::withTrashed()->whereIn('id', $topAdmins->pluck('admin_id'))->pluck('name', 'id');

        return [
            'today' => $today,
            'days' => $days,
            'topTools' => $this->callsByTool(30)->sortDesc()->take(5),
            'topAdmins' => $topAdmins->map(fn ($row) => ['name' => $adminNames[$row->admin_id] ?? ('#' . $row->admin_id), 'total' => (int) $row->total]),
            'recentLogs' => McpAuditLog::query()->latest('id')->limit(8)->get(),
            'logAdmins' => $this->adminNamesFor(McpAuditLog::query()->latest('id')->limit(8)->pluck('admin_id')),
            'checks' => $this->setupChecks($settings, $tools),
        ];
    }

    private function employeesData(): array
    {
        $accesses = McpAdminAccess::query()->with('admin:id,name,email,branch_id,status,deleted_at')->orderByDesc('enabled')->orderBy('id')->get();
        $adminIds = $accesses->pluck('admin_id');

        $connections = $this->activeTokens()->whereIn('admin_id', $adminIds)
            ->selectRaw('admin_id, COUNT(*) as total, MAX(last_used_at) as last_used')
            ->groupBy('admin_id')->get()->keyBy('admin_id');

        $calls = McpAuditLog::query()->where('method', 'tools/call')->where('created_at', '>=', now()->subDays(30))
            ->whereIn('admin_id', $adminIds)->selectRaw('admin_id, COUNT(*) as total')->groupBy('admin_id')->pluck('total', 'admin_id');

        return [
            'accesses' => $accesses,
            'connections' => $connections,
            'calls' => $calls,
            'candidates' => Admin::query()
                ->whereNotIn('id', $adminIds)
                ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'suspended'))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'branch_id']),
        ];
    }

    private function connectionsData(): array
    {
        $tokens = McpAccessToken::query()
            ->with(['admin:id,name,email,branch_id,status', 'oauthClient:id,client_name'])
            ->when(request('show') !== 'all', fn ($query) => $query->whereIn('id', $this->activeTokens()->select('id')))
            ->latest('id')
            ->limit(200)
            ->get();

        $clients = McpOAuthClient::query()->latest('id')->limit(100)->get();
        $clientStats = McpAccessToken::query()->whereIn('oauth_client_id', $clients->pluck('id'))
            ->selectRaw('oauth_client_id, SUM(CASE WHEN revoked_at IS NULL AND refresh_expires_at > ? THEN 1 ELSE 0 END) as active, MAX(last_used_at) as last_used', [now()])
            ->groupBy('oauth_client_id')->get()->keyBy('oauth_client_id');

        return [
            'tokens' => $tokens,
            'clients' => $clients,
            'clientStats' => $clientStats,
            'admins' => Admin::query()
                ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'suspended'))
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'branch_id']),
        ];
    }

    private function logsData(Request $request, Collection $tools): array
    {
        $logs = $this->filteredLogs($request)->latest('id')->paginate(50)->withQueryString();

        return [
            'logs' => $logs,
            'logAdmins' => $this->adminNamesFor($logs->pluck('admin_id')),
            'filterAdmins' => Admin::withTrashed()
                ->whereIn('id', McpAuditLog::query()->whereNotNull('admin_id')->distinct()->pluck('admin_id'))
                ->orderBy('name')->pluck('name', 'id'),
            'filterTools' => $tools->keys()->sort()->values(),
        ];
    }

    private function filteredLogs(Request $request)
    {
        return McpAuditLog::query()
            ->when($request->filled('admin_id'), fn ($query) => $query->where('admin_id', (int) $request->query('admin_id')))
            ->when($request->filled('tool'), fn ($query) => $query->where('tool', (string) $request->query('tool')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->query('status')))
            ->when($request->filled('from'), fn ($query) => $query->where('created_at', '>=', $this->date($request->query('from'))))
            ->when($request->filled('to'), fn ($query) => $query->where('created_at', '<', $this->date($request->query('to'))->addDay()));
    }

    // ------------------------------------------------------------------ مساعدات

    /** توكن يدوي غير منتهٍ، أو ربط OAuth ما زال قابلاً للتجديد. */
    private function activeTokens()
    {
        return McpAccessToken::query()
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->where(fn ($q) => $q->whereNull('oauth_client_id')->where(fn ($e) => $e->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                    ->orWhere(fn ($q) => $q->whereNotNull('oauth_client_id')->where('refresh_expires_at', '>', now()));
            });
    }

    private function callsByTool(int $days): Collection
    {
        return McpAuditLog::query()->where('method', 'tools/call')->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('tool')->selectRaw('tool, COUNT(*) as total')->groupBy('tool')->pluck('total', 'tool')
            ->map(fn ($total) => (int) $total);
    }

    private function setupChecks(array $settings, Collection $tools): array
    {
        $appUrl = (string) config('app.url');
        $enabledAdmins = McpAdminAccess::query()->where('enabled', true)->count();
        $enabledTools = $tools->filter(fn (McpTool $tool) => McpSettings::toolEnabled($tool->name()))->count();

        return [
            ['ok' => $settings['enabled'], 'label' => 'الخادم مفعّل', 'hint' => 'من تبويب الإعدادات. قبل التفعيل يرجع الرابط 404.'],
            ['ok' => str_starts_with($appUrl, 'https://'), 'label' => 'APP_URL على https', 'hint' => 'القيمة الحالية: ' . ($appUrl ?: 'فارغة') . '. claude.ai وChatGPT يرفضان رابطاً غير مطابق.'],
            ['ok' => $settings['oauth_enabled'], 'label' => 'الربط بتسجيل الدخول (OAuth)', 'hint' => 'مطلوب لـ claude.ai وChatGPT. Codex يعمل بتوكن يدوي بدونه.'],
            ['ok' => $settings['access_mode'] === McpSettings::ACCESS_ALL || $enabledAdmins > 0, 'label' => 'موظف واحد مفعّل على الأقل', 'hint' => "في وضع «المحددون فقط» لا يعمل الخادم لأحد قبل إضافة موظفين. المفعّلون: {$enabledAdmins}."],
            ['ok' => $enabledTools > 0, 'label' => 'أداة واحدة مفعّلة على الأقل', 'hint' => "المفعّل {$enabledTools} من {$tools->count()}."],
            ['ok' => $settings['mask_sensitive'], 'label' => 'إخفاء الأرقام الحساسة', 'hint' => 'الجواز والجوال يظهران بآخر 4 أرقام فقط.'],
        ];
    }

    private function adminNamesFor($ids): Collection
    {
        return Admin::withTrashed()->whereIn('id', collect($ids)->filter()->unique())->pluck('name', 'id');
    }

    private function date($value): \Illuminate\Support\Carbon
    {
        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable $e) {
            return today();
        }
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('mcp_access_tokens') && Schema::hasTable('mcp_audit_logs')
            && Schema::hasTable('mcp_oauth_clients') && Schema::hasTable('mcp_admin_access');
    }

    private function returnTab(Request $request, string $default): string
    {
        $tab = (string) $request->input('return_tab');

        return array_key_exists($tab, self::TABS) ? $tab : $default;
    }

    private function back(string $tab, string $message)
    {
        return redirect()->route('dev-superadmin.mcp.index', ['tab' => $tab])->with('success', $message);
    }
}
