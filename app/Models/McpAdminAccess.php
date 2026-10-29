<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

/** صلاحية موظف على خادم MCP كما يضبطها Super Admin: مفعّل، المستوى، والأدوات المسموحة. */
class McpAdminAccess extends Model
{
    protected $table = 'mcp_admin_access';

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'allowed_tools' => 'array',
    ];

    public const LEVEL_READ = 'read';

    public const LEVEL_WRITE = 'write';

    public const LEVELS = [self::LEVEL_READ, self::LEVEL_WRITE];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id')->withTrashed();
    }

    public function canWrite(): bool
    {
        return $this->access_level === self::LEVEL_WRITE;
    }

    public function allowsTool(string $name): bool
    {
        return $this->allowed_tools === null || in_array($name, $this->allowed_tools, true);
    }
}
