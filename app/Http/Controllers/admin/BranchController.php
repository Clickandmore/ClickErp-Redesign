<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\SystemModuleManager;
use App\Support\ZohoSalesIqSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query()->ordered();

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $branches = $query->with(['manager', 'admins'])->paginate(20);
        $branchesCount = Branch::query()->count();
        $branchLimit = SystemModuleManager::branchLimit();
        $canCreateBranch = $branchLimit === null || $branchesCount < $branchLimit;

        return view('admin.branches.index', compact('branches', 'branchesCount', 'branchLimit', 'canCreateBranch'));
    }

    public function create()
    {
        if (! $this->canCreateBranch()) {
            return redirect()
                ->route('branches.index')
                ->withErrors(['branches_limit' => $this->branchLimitMessage()]);
        }

        return view('admin.branches.create', [
            'branchesCount' => Branch::query()->count(),
            'branchLimit' => SystemModuleManager::branchLimit(),
        ]);
    }

    public function settings()
    {
        return view('admin.branches.settings', [
            'branchesCount' => Branch::query()->count(),
            'activeBranchesCount' => Branch::query()->where('is_active', true)->count(),
            'branchLimit' => SystemModuleManager::branchLimit(),
        ]);
    }

    public function report(Request $request)
    {
        $branchService = app(\App\Services\BranchAccessService::class);
        $adminBranchId = $branchService->adminBranchId();

        $branches = Branch::query()
            ->ordered()
            ->when($adminBranchId, fn ($q) => $q->whereKey($adminBranchId))
            ->get(['id', 'name_ar', 'name_en', 'code', 'is_active']);

        $selectedBranchId = $adminBranchId ?: (int) $request->query('branch_id', 0);
        $reportBranches = $selectedBranchId > 0
            ? $branches->where('id', $selectedBranchId)
            : $branches;

        $rows = $reportBranches->map(function (Branch $branch) {
            return [
                'branch' => $branch,
                'clients' => $this->countBranchRows('users', $branch->id),
                'admins' => $this->countBranchRows('admins', $branch->id),
                'orders' => $this->countBranchRows('orders', $branch->id),
                'contracts' => $this->countBranchRows('contracts', $branch->id),
                'rental_contracts' => $this->countBranchRows('rental_contracts', $branch->id),
                'invoices' => $this->countBranchRows('invoices', $branch->id),
                'rental_invoices' => $this->countBranchRows('rental_contract_invoices', $branch->id),
                'complaints' => $this->countBranchRows('complaints', $branch->id),
                'financial_requests' => $this->countBranchRows('financial_requests', $branch->id),
                'sponsorship_transfers' => $this->countBranchRows('sponsorship_transfers', $branch->id),
                'transfer_contracts' => $this->countBranchRows('transfer_service_contracts', $branch->id),
                'delegations' => $this->countBranchRows('ingaz_delegations', $branch->id),
                'musaned_transfers' => $this->countBranchRows('musaned_transfers', $branch->id),
                'return_requests' => $this->countBranchRows('contract_return_requests', $branch->id),
                'invoice_total' => $this->sumBranchRows('invoices', $branch->id, ['total', 'total_amount', 'amount', 'final_total']),
                'rental_invoice_total' => $this->sumBranchRows('rental_contract_invoices', $branch->id, ['total', 'total_amount', 'amount', 'invoice_amount']),
            ];
        })->values();

        $totals = [];
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                if ($key === 'branch') {
                    continue;
                }

                $totals[$key] = ($totals[$key] ?? 0) + $value;
            }
        }

        return view('admin.branches.report', [
            'branches' => $branches,
            'rows' => $rows,
            'totals' => $totals,
            'selectedBranchId' => $selectedBranchId,
        ]);
    }
    public function store(Request $request)
    {
        if (! $this->canCreateBranch()) {
            return redirect()
                ->route('branches.index')
                ->withErrors(['branches_limit' => $this->branchLimitMessage()]);
        }

        $data = $this->validatedData($request);
        $data['code'] = $data['code'] ?: $this->generateCode();

        Branch::create($data);

        return redirect()
            ->route('branches.index')
            ->with('success', 'تم إضافة الفرع بنجاح');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validatedData($request, $branch));

        return redirect()
            ->route('branches.index')
            ->with('success', 'تم تحديث الفرع بنجاح');
    }

    public function assignManager(Request $request, Branch $branch)
    {
        $request->validate([
            'admin_id' => ['nullable', 'integer', 'exists:admins,id'],
        ]);

        DB::transaction(function () use ($branch, $request) {
            // Clear current manager flag
            DB::table('admins')
                ->where('branch_id', $branch->id)
                ->where('is_branch_manager', true)
                ->update(['is_branch_manager' => false]);

            if ($request->filled('admin_id')) {
                // Assign admin to this branch and mark as responsible
                DB::table('admins')
                    ->where('id', $request->admin_id)
                    ->whereIn('admin_type', ['admin', 'super_admin'])
                    ->update(['branch_id' => $branch->id, 'is_branch_manager' => true]);
            }
        });

        return response()->json(['message' => 'تم تحديث مدير الفرع']);
    }

    public function branchAdmins(Branch $branch)
    {
        // Return ALL real admins so user can pick any one as responsible
        $currentManagerId = \App\Models\auth\Admin::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch_manager', true)
            ->whereNull('deleted_at')
            ->value('id');

        $admins = \App\Models\auth\Admin::query()
            ->whereIn('admin_type', ['admin', 'super_admin'])
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id'])
            ->map(fn ($a) => [
                'id'         => $a->id,
                'name'       => $a->name,
                'is_selected'=> $a->id === $currentManagerId,
            ]);

        return response()->json($admins);
    }

    public function availableAdmins(Branch $branch)
    {
        // All real admins NOT already in this branch
        $admins = \App\Models\auth\Admin::query()
            ->whereIn('admin_type', ['admin', 'super_admin'])
            ->where(function ($q) use ($branch) {
                $q->whereNull('branch_id')->orWhere('branch_id', '!=', $branch->id);
            })
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        $branches = Branch::query()->get(['id', 'name_ar'])->keyBy('id');

        return response()->json($admins->map(fn ($a) => [
            'id'          => $a->id,
            'name'        => $a->name,
            'branch_name' => $a->branch_id ? ($branches[$a->branch_id]->name_ar ?? null) : null,
        ]));
    }

    public function assignAdmin(Request $request, Branch $branch)
    {
        $request->validate([
            'admin_id' => ['required', 'integer', 'exists:admins,id'],
        ]);

        DB::table('admins')
            ->where('id', $request->admin_id)
            ->whereIn('admin_type', ['admin', 'super_admin'])
            ->update(['branch_id' => $branch->id]);

        return response()->json(['message' => 'تم إضافة الموظف للفرع']);
    }

    public function removeAdmin(Request $request, Branch $branch)
    {
        $request->validate([
            'admin_id' => ['required', 'integer', 'exists:admins,id'],
        ]);

        DB::table('admins')
            ->where('id', $request->admin_id)
            ->where('branch_id', $branch->id)
            ->update(['branch_id' => null, 'is_branch_manager' => false]);

        return response()->json(['message' => 'تم إزالة الموظف من الفرع']);
    }

    public function destroy(Request $request, Branch $branch)
    {
        $branch->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفرع بنجاح',
            ]);
        }

        return redirect()
            ->route('branches.index')
            ->with('success', 'تم حذف الفرع بنجاح');
    }

    private function countBranchRows(string $table, int $branchId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
            return 0;
        }

        return (int) DB::table($table)->where('branch_id', $branchId)->count();
    }

    private function sumBranchRows(string $table, int $branchId, array $candidateColumns): float
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
            return 0.0;
        }

        $column = collect($candidateColumns)->first(fn ($candidate) => Schema::hasColumn($table, $candidate));
        if (! $column) {
            return 0.0;
        }

        return (float) DB::table($table)->where('branch_id', $branchId)->sum($column);
    }
    private function validatedData(Request $request, ?Branch $branch = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.is_closed' => ['nullable', 'boolean'],
            'working_hours.*.from' => ['nullable', 'date_format:H:i'],
            'working_hours.*.to' => ['nullable', 'date_format:H:i'],
            'zoho_salesiq_settings' => ['nullable', 'array'],
            'zoho_salesiq_settings.salesiq_base_url' => ['nullable', 'url', 'max:500'],
            'zoho_salesiq_settings.accounts_base_url' => ['nullable', 'url', 'max:500'],
            'zoho_salesiq_settings.screen_name' => ['nullable', 'string', 'max:255'],
            'zoho_salesiq_settings.client_id' => ['nullable', 'string', 'max:255'],
            'zoho_salesiq_settings.client_secret' => ['nullable', 'string', 'max:1000'],
            'zoho_salesiq_settings.refresh_token' => ['nullable', 'string', 'max:1000'],
            'zoho_salesiq_settings.widget_url' => ['nullable', 'url', 'max:1000'],
            'zoho_salesiq_settings.timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['working_hours'] = $this->normalizeWorkingHours($data['working_hours'] ?? []);
        $data['zoho_salesiq_settings'] = ZohoSalesIqSettings::normalizeForStorage(
            $data['zoho_salesiq_settings'] ?? [],
            $branch?->zoho_salesiq_settings ?? []
        );

        return $data;
    }

    private function generateCode(): string
    {
        $lastId = (int) Branch::query()->max('id');

        do {
            $code = 'BR-' . str_pad((string) (++$lastId), 4, '0', STR_PAD_LEFT);
        } while (Branch::query()->where('code', $code)->exists());

        return $code;
    }

    private function canCreateBranch(): bool
    {
        $limit = SystemModuleManager::branchLimit();

        return $limit === null || Branch::query()->count() < $limit;
    }

    private function branchLimitMessage(): string
    {
        $limit = SystemModuleManager::branchLimit();

        return $limit === null
            ? 'لا يمكن إضافة فرع جديد حالياً.'
            : "لا يمكن إضافة فرع جديد لأن العدد المسموح به للفروع هو {$limit}.";
    }

    private function normalizeWorkingHours(array $hours): array
    {
        $days = [
            'saturday',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
        ];

        return collect($days)
            ->mapWithKeys(function (string $day) use ($hours) {
                $row = $hours[$day] ?? [];
                $isClosed = (bool) ($row['is_closed'] ?? false);

                return [$day => [
                    'is_closed' => $isClosed,
                    'from' => $isClosed ? null : ($row['from'] ?? null),
                    'to' => $isClosed ? null : ($row['to'] ?? null),
                ]];
            })
            ->all();
    }
}
