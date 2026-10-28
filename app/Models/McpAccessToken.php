<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * توكن وصول لخادم MCP. يُخزَّن الـ hash فقط؛ النص الأصلي يظهر مرة واحدة عند الإصدار.
 */
class McpAccessToken extends Model
{
    protected $table = 'mcp_access_tokens';

    protected $guarded = [];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public const PREFIX = 'cerp_mcp_';

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * @return array{0: self, 1: string} السجل والتوكن النصي (لا يمكن استرجاعه لاحقاً)
     */
    public static function issue(Admin $admin, string $name, ?int $days = null): array
    {
        $plain = self::PREFIX . Str::random(48);

        $token = self::query()->create([
            'admin_id' => $admin->id,
            'name' => $name,
            'token_hash' => self::hash($plain),
            'expires_at' => $days ? now()->addDays($days) : null,
        ]);

        return [$token, $plain];
    }

    public static function findActive(string $plain): ?self
    {
        if ($plain === '' || !str_starts_with($plain, self::PREFIX)) {
            return null;
        }

        $token = self::query()->where('token_hash', self::hash($plain))->first();

        return $token && $token->isActive() ? $token : null;
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
