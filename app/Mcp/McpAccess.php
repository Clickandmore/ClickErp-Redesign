<?php

namespace App\Mcp;

use App\Mcp\Tools\McpTool;
use App\Models\auth\Admin;
use App\Models\McpAdminAccess;
use App\Support\McpSettings;
use Illuminate\Support\Facades\Schema;

/**
 * تحكم Super Admin في من يستخدم MCP وأي أداة.
 *
 * يأتي فوق صلاحيات الشاشات لا بدلاً منها: الشاشة تضيّق ولا توسّع، فالموظف لا يصل
 * عبر MCP إلى أكثر مما يراه في لوحة التحكم (McpTool::isAllowedFor).
 */
class McpAccess
{
    /** @var array<int, McpAdminAccess|null> */
    private array $records = [];

    public function recordFor(Admin $admin): ?McpAdminAccess
    {
        if (!array_key_exists($admin->id, $this->records)) {
            $this->records[$admin->id] = Schema::hasTable('mcp_admin_access')
                ? McpAdminAccess::query()->where('admin_id', $admin->id)->first()
                : null;
        }

        return $this->records[$admin->id];
    }

    public function adminAllowed(Admin $admin): bool
    {
        $record = $this->recordFor($admin);

        // سطر موقوف يمنع الموظف حتى في وضع «الجميع»؛ هكذا يُستثنى شخص بعينه.
        if ($record && !$record->enabled) {
            return false;
        }

        return McpSettings::accessMode() === McpSettings::ACCESS_ALL || $record !== null;
    }

    /** إعدادات Super Admin فقط؛ صلاحية الشاشة تُفحص منفصلة في McpTool::isAllowedFor. */
    public function toolAllowed(Admin $admin, McpTool $tool): bool
    {
        if (!McpSettings::toolEnabled($tool->name()) || !$this->adminAllowed($admin)) {
            return false;
        }

        $record = $this->recordFor($admin);

        if ($tool->isWriteTool() && (!McpSettings::writeEnabled() || !$record?->canWrite())) {
            return false;
        }

        return !$record || $record->allowsTool($tool->name());
    }
}
