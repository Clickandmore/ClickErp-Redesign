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
        'refresh_expires_at' => 'datetime',
    ];

    public const PREFIX = 'cerp_mcp_';

    public const REFRESH_PREFIX = 'cerp_mcpr_';

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function oauthClient()
    {
        return $this->belongsTo(McpOAuthClient::class, 'oauth_client_id');
    }

    /**
     * توكن قصير العمر + refresh token، يصدران بعد موافقة الموظف في شاشة OAuth.
     *
     * @return array{0: self, 1: string, 2: string} السجل، access token، refresh token
     */
    public static function issueForOAuth(Admin $admin, McpOAuthClient $client, int $accessMinutes, int $refreshDays): array
    {
        $access = self::PREFIX . Str::random(48);
        $refresh = self::REFRESH_PREFIX . Str::random(48);

        $token = self::query()->create([
            'admin_id' => $admin->id,
            'oauth_client_id' => $client->id,
            'name' => Str::limit($client->client_name ?: 'OAuth client', 110),
            'token_hash' => self::hash($access),
            'refresh_token_hash' => self::hash($refresh),
            'expires_at' => now()->addMinutes($accessMinutes),
            'refresh_expires_at' => now()->addDays($refreshDays),
        ]);

        return [$token, $access, $refresh];
    }

    public static function findByRefreshToken(string $plain): ?self
    {
        if ($plain === '' || !str_starts_with($plain, self::REFRESH_PREFIX)) {
            return null;
        }

        $token = self::query()->where('refresh_token_hash', self::hash($plain))->first();

        if (!$token || $token->revoked_at !== null
            || ($token->refresh_expires_at !== null && $token->refresh_expires_at->isPast())) {
            return null;
        }

        return $token;
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

    /** يقطع كل اتصالات الموظف (يدوية وOAuth) فوراً، والتجديد يتوقف معها. */
    public static function revokeAllFor(int $adminId): int
    {
        return self::query()
            ->where('admin_id', $adminId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now(), 'updated_at' => now()]);
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
