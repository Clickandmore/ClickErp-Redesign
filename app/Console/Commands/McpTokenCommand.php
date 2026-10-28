<?php

namespace App\Console\Commands;

use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Support\McpSettings;
use Illuminate\Console\Command;

class McpTokenCommand extends Command
{
    protected $signature = 'mcp:token
        {action : issue, list or revoke}
        {--admin= : Admin id, email or username (issue / list)}
        {--name= : Label for the token, e.g. "Codex - Ahmed laptop"}
        {--days= : Days until expiry (default config mcp.token_days, 0 = never)}
        {--id= : Token id (revoke)}';

    protected $description = 'Issue, list or revoke ClickERP MCP access tokens';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'issue' => $this->issue(),
            'list' => $this->listTokens(),
            'revoke' => $this->revoke(),
            default => $this->invalid('Unknown action. Allowed: issue, list, revoke.'),
        };
    }

    private function issue(): int
    {
        $admin = $this->findAdmin();
        if (!$admin) {
            return $this->invalid('Admin not found. Pass --admin=<id|email|username>.');
        }

        if (($admin->status ?? 'active') === 'suspended') {
            return $this->invalid('This admin is suspended.');
        }

        $days = $this->option('days') === null ? McpSettings::tokenDays() : (int) $this->option('days');
        $name = (string) ($this->option('name') ?: 'MCP token');

        [$token, $plain] = McpAccessToken::issue($admin, $name, $days > 0 ? $days : null);

        $this->info("Token #{$token->id} issued for {$admin->name} (admin #{$admin->id}).");
        $this->line('Expires: ' . ($token->expires_at?->toDateString() ?? 'never'));
        $this->newLine();
        $this->line($plain);
        $this->newLine();
        $this->warn('Copy it now — it is stored hashed and cannot be shown again.');

        if (!McpSettings::enabled()) {
            $this->warn('MCP is disabled. Enable it from Super Admin > MCP (or MCP_ENABLED=true in .env).');
        }

        return self::SUCCESS;
    }

    private function listTokens(): int
    {
        $query = McpAccessToken::query()->with('admin')->latest('id');

        if ($this->option('admin')) {
            $admin = $this->findAdmin();
            if (!$admin) {
                return $this->invalid('Admin not found.');
            }
            $query->where('admin_id', $admin->id);
        }

        $this->table(
            ['ID', 'Admin', 'Name', 'Last used', 'Expires', 'State'],
            $query->get()->map(fn (McpAccessToken $token) => [
                $token->id,
                $token->admin?->name ?? "#{$token->admin_id}",
                $token->name,
                $token->last_used_at?->toDateTimeString() ?? '-',
                $token->expires_at?->toDateString() ?? 'never',
                $token->revoked_at ? 'revoked' : ($token->isActive() ? 'active' : 'expired'),
            ])->all()
        );

        return self::SUCCESS;
    }

    private function revoke(): int
    {
        $token = McpAccessToken::query()->find((int) $this->option('id'));
        if (!$token) {
            return $this->invalid('Token not found. Pass --id=<token id>.');
        }

        $token->forceFill(['revoked_at' => $token->revoked_at ?? now()])->save();
        $this->info("Token #{$token->id} revoked.");

        return self::SUCCESS;
    }

    private function findAdmin(): ?Admin
    {
        $value = (string) $this->option('admin');
        if ($value === '') {
            return null;
        }

        return Admin::query()
            ->where(function ($query) use ($value) {
                $query->where('email', $value)->orWhere('username', $value);
                if (ctype_digit($value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->first();
    }

    private function invalid(string $message): int
    {
        $this->error($message);

        return self::INVALID;
    }
}
