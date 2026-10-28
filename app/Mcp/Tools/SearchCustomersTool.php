<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Models\ingaz\User;
use App\PermKeys;
use App\Services\BranchAccessService;
use App\Services\BranchDataSharingService;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\SystemModuleManager;

/**
 * البحث في العملاء. موديل User بلا BranchScoped، فيُطبَّق هنا نفس عزل
 * UserController::applyBranchScope (مع مشاركة العملاء بين الفروع).
 */
class SearchCustomersTool extends McpTool
{
    public function name(): string
    {
        return 'search_customers';
    }

    public function description(): string
    {
        return 'البحث عن العملاء بالاسم أو الجوال أو رقم الهوية، مع عدد عقود الاستقدام والإيجار لكل عميل. '
            . 'Search customers by name, phone or national id, with their contract counts.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => ['type' => 'string', 'description' => 'اسم العميل أو جواله أو رقم هويته'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10],
            ],
            'required' => ['query'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'clients.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $term = trim((string) ($arguments['query'] ?? ''));
        $query = User::query()->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('national_num', $term));

        $this->applyBranchScope($query, $admin);

        $total = (clone $query)->count();
        $customers = $query->with('branch')->latest('id')->limit($this->limit($arguments, 10))->get();

        $recruitment = \App\Models\Contract::query()->whereIn('type', ['recruitment', 'maid'])
            ->whereIn('user_id', $customers->pluck('id'))->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id');
        $rental = \App\Models\RentalContract::query()
            ->whereIn('user_id', $customers->pluck('id'))->selectRaw('user_id, count(*) as total')->groupBy('user_id')->pluck('total', 'user_id');

        return [
            'total' => $total,
            'customers' => $customers->map(fn (User $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $this->mask($customer->phone),
                'national_id' => $this->mask($customer->national_num),
                'city' => $customer->city,
                'status' => $customer->status,
                'branch' => $customer->branch?->name,
                'recruitment_contracts' => (int) ($recruitment[$customer->id] ?? 0),
                'rental_contracts' => (int) ($rental[$customer->id] ?? 0),
                'created_at' => $this->date($customer->created_at),
            ])->all(),
        ];
    }

    private function applyBranchScope($query, Admin $admin): void
    {
        $enabled = SystemModuleManager::isEnabled('branches');
        if (!$enabled || AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::BRANCHES_VIEW_ALL, PermKeys::CLIENTS_VIEW_ALL_BRANCHES])) {
            return;
        }

        if ($admin->branch_id) {
            app(BranchAccessService::class)->applyBranchIdToQuery(
                $query,
                (int) $admin->branch_id,
                'users',
                null,
                app(BranchDataSharingService::class)->sharedSourceBranchIds(BranchDataSharingService::CLIENTS)
            );

            return;
        }

        $query->whereRaw('1 = 0');
    }
}
