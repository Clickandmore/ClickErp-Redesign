<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** رمز تفويض قصير العمر (10 دقائق، استخدام واحد) يُستبدل بتوكن في /oauth/token. */
class McpOAuthCode extends Model
{
    protected $table = 'mcp_oauth_codes';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
