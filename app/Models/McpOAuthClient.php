<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** عميل OAuth سجّل نفسه عبر Dynamic Client Registration (claude.ai، ChatGPT، Claude Code...). */
class McpOAuthClient extends Model
{
    protected $table = 'mcp_oauth_clients';

    protected $guarded = [];

    protected $casts = [
        'redirect_uris' => 'array',
        'revoked_at' => 'datetime',
    ];

    public function isPublic(): bool
    {
        return $this->token_endpoint_auth_method === 'none';
    }

    public function allowsRedirect(string $uri): bool
    {
        return in_array($uri, (array) $this->redirect_uris, true);
    }

    public function verifySecret(?string $secret): bool
    {
        return $this->client_secret_hash !== null
            && $secret !== null
            && hash_equals($this->client_secret_hash, hash('sha256', $secret));
    }
}
