<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** سجل كل طلب وصل إلى خادم MCP: من طلب، أي أداة، بأي مدخلات، وماذا كانت النتيجة. */
class McpAuditLog extends Model
{
    protected $table = 'mcp_audit_logs';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'arguments' => 'array',
    ];
}
