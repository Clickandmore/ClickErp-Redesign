<?php

namespace App\Console\Commands;

use App\Services\AdminNotificationService;
use App\Support\McpSettings;
use App\Support\McpSubscription;
use Illuminate\Console\Command;

/**
 * تنبيهات انتهاء اشتراك المساعد الذكي للموظفين المحددين في Super Admin ← MCP ← الاشتراك.
 * يعمل يومياً، ويرسل في أيام محددة فقط حتى لا يتكرر كل يوم.
 */
class McpSubscriptionAlertsCommand extends Command
{
    private const ALERT_DAYS = [McpSubscription::WARN_DAYS, 3];

    protected $signature = 'mcp:subscription-alerts';

    protected $description = 'Notify selected admins before and after the MCP subscription ends';

    public function handle(AdminNotificationService $notifications): int
    {
        $admins = McpSettings::all()['subscription_alert_admins'];
        $days = McpSubscription::daysLeft();

        if ($admins === [] || $days === null || !McpSettings::enabled()) {
            return self::SUCCESS;
        }

        $state = McpSubscription::state();
        $firstGraceDay = $state === McpSubscription::STATE_GRACE && $days === -1;
        $upcoming = $state === McpSubscription::STATE_ACTIVE && in_array($days, self::ALERT_DAYS, true);
        $justExpired = $state === McpSubscription::STATE_EXPIRED
            && today()->subDay()->toDateString() === McpSubscription::summary()['grace_ends_at'];

        if (!$upcoming && !$firstGraceDay && !$justExpired) {
            return self::SUCCESS;
        }

        $notifications->sendToAdmins(
            $admins,
            'اشتراك المساعد الذكي',
            (string) McpSubscription::message(),
            'mcp_subscription',
            McpSettings::guidePageEnabled() ? route('mcp.guide') : null
        );

        $this->info('Alert sent to ' . count($admins) . ' admin(s).');

        return self::SUCCESS;
    }
}
