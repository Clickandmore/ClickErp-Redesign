<?php

namespace App\Console\Commands;

use App\Support\McpSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * تنظيف جداول MCP يومياً. بدونه تكبر الجداول بلا حد: كل ربط OAuth يضيف صفاً
 * في mcp_access_tokens مع كل تجديد (كل ساعة تقريباً).
 */
class McpPruneCommand extends Command
{
    protected $signature = 'mcp:prune
        {--token-days=7 : Keep revoked/expired tokens this many days before deleting}
        {--dry-run : Show counts without deleting}';

    protected $description = 'Delete old MCP tokens, OAuth codes, unused OAuth clients and audit logs past the retention period';

    public function handle(): int
    {
        if (!Schema::hasTable('mcp_access_tokens')) {
            $this->warn('MCP tables are missing; nothing to prune.');

            return self::SUCCESS;
        }

        $tokenCutoff = now()->subDays(max(0, (int) $this->option('token-days')));

        $counts = [
            'tokens' => $this->prune(DB::table('mcp_access_tokens')->where(function ($query) use ($tokenCutoff) {
                $query->where('revoked_at', '<', $tokenCutoff)
                    // يدوي منتهٍ، أو OAuth انتهى توكن تجديده (لم يعد قابلاً للتجديد).
                    ->orWhere(fn ($q) => $q->whereNull('oauth_client_id')->where('expires_at', '<', $tokenCutoff))
                    ->orWhere(fn ($q) => $q->whereNotNull('oauth_client_id')->where('refresh_expires_at', '<', $tokenCutoff));
            })),
        ];

        if (Schema::hasTable('mcp_oauth_codes')) {
            $counts['oauth_codes'] = $this->prune(DB::table('mcp_oauth_codes')->where('expires_at', '<', now()->subDay()));
        }

        if (Schema::hasTable('mcp_oauth_clients')) {
            // عميل سجّل نفسه ولم يُكمل ربطاً خلال أسبوع، أو أُلغي ولم يبق له توكن.
            $counts['oauth_clients'] = $this->prune(DB::table('mcp_oauth_clients')
                ->where('created_at', '<', now()->subDays(7))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('mcp_access_tokens')
                    ->whereColumn('mcp_access_tokens.oauth_client_id', 'mcp_oauth_clients.id'))
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('mcp_oauth_codes')
                    ->whereColumn('mcp_oauth_codes.client_id', 'mcp_oauth_clients.id')));
        }

        $retention = McpSettings::auditRetentionDays();
        if ($retention > 0 && Schema::hasTable('mcp_audit_logs')) {
            $counts['audit_logs'] = $this->prune(DB::table('mcp_audit_logs')->where('created_at', '<', now()->subDays($retention)));
        }

        foreach ($counts as $table => $count) {
            $this->line(sprintf('%s: %d %s', $table, $count, $this->option('dry-run') ? 'would be deleted' : 'deleted'));
        }

        return self::SUCCESS;
    }

    private function prune($query): int
    {
        return $this->option('dry-run') ? $query->count() : $query->delete();
    }
}
