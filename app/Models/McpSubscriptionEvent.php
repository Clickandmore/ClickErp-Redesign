<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** سجل تغييرات اشتراك المساعد الذكي: مرجع عند التجديد أو أي خلاف مع العميل. */
class McpSubscriptionEvent extends Model
{
    protected $table = 'mcp_subscription_events';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'previous' => 'array',
    ];
}
