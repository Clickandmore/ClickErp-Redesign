<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Services\BranchAccessService;

class WhoAmITool extends McpTool
{
    public function name(): string
    {
        return 'whoami';
    }

    public function description(): string
    {
        return 'بيانات الموظف المرتبط بالتوكن: الاسم والفرع والأدوار. '
            . 'Returns the ClickERP employee this token acts as, with branch and roles.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass()];
    }

    protected function gateRoute(): ?string
    {
        return null;
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $branch = $admin->branch_id ? $admin->branch : null;
        $branches = app(BranchAccessService::class);
        $isolated = $branch && $branches->enabled() && ! $branches->canViewAllBranches($admin);

        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'branch' => $branch ? ['id' => $branch->id, 'name' => $branch->name] : null,
            'scope' => $isolated ? 'own_branch_only' : 'all_branches',
            'roles' => $admin->roles()->pluck('name')->values()->all(),
        ];
    }
}
