<?php

namespace App\Support;

use App\Models\NewSettings;
use App\Models\ingaz\Setting;

class AutomationSettings
{
    private const DEFAULTS = [
        'warranty_period_days' => 90,
        'order_expiration_hours' => 24,
        'order_new_deadline_hours' => 24,
        'order_under_process_deadline_hours' => 48,
        'order_urgent_threshold_hours' => 2,
        'old_pending_orders_days' => 30,
        'warranty_status_update_time' => '01:00',
        'warranty_initialize_interval_hours' => 6,
        'orders_timer_interval_minutes' => 30,
        'orders_expired_handler_interval_minutes' => 5,
        'orders_auto_cancel_interval_minutes' => 60,
    ];

    private static array $cache = [];

    public static function keys(): array
    {
        return array_keys(self::DEFAULTS);
    }

    public static function all(): array
    {
        return [
            'warranty_period_days' => self::warrantyPeriodDays(),
            'order_expiration_hours' => self::orderExpirationHours(),
            'order_new_deadline_hours' => self::orderNewDeadlineHours(),
            'order_under_process_deadline_hours' => self::orderUnderProcessDeadlineHours(),
            'order_urgent_threshold_hours' => self::orderUrgentThresholdHours(),
            'old_pending_orders_days' => self::oldPendingOrdersDays(),
            'warranty_status_update_time' => self::warrantyStatusUpdateTime(),
            'warranty_initialize_interval_hours' => self::warrantyInitializeIntervalHours(),
            'orders_timer_interval_minutes' => self::ordersTimerIntervalMinutes(),
            'orders_expired_handler_interval_minutes' => self::ordersExpiredHandlerIntervalMinutes(),
            'orders_auto_cancel_interval_minutes' => self::ordersAutoCancelIntervalMinutes(),
        ];
    }

    public static function persist(array $values): void
    {
        foreach (self::keys() as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            self::set($key, $values[$key]);
        }
    }

    public static function warrantyPeriodDays(): int
    {
        return max(1, (int) self::get('warranty_period_days'));
    }

    public static function orderExpirationHours(): int
    {
        return max(1, (int) self::get('order_expiration_hours'));
    }

    public static function orderNewDeadlineHours(): int
    {
        return max(1, (int) self::get('order_new_deadline_hours'));
    }

    public static function orderUnderProcessDeadlineHours(): int
    {
        return max(1, (int) self::get('order_under_process_deadline_hours'));
    }

    public static function orderUrgentThresholdHours(): int
    {
        return max(1, (int) self::get('order_urgent_threshold_hours'));
    }

    public static function oldPendingOrdersDays(): int
    {
        return max(1, (int) self::get('old_pending_orders_days'));
    }

    public static function warrantyStatusUpdateTime(): string
    {
        $time = (string) self::get('warranty_status_update_time');

        return preg_match('/^\d{2}:\d{2}$/', $time) ? $time : self::DEFAULTS['warranty_status_update_time'];
    }

    public static function warrantyInitializeIntervalHours(): int
    {
        return max(1, min(24, (int) self::get('warranty_initialize_interval_hours')));
    }

    public static function ordersTimerIntervalMinutes(): int
    {
        return max(1, min(60, (int) self::get('orders_timer_interval_minutes')));
    }

    public static function ordersExpiredHandlerIntervalMinutes(): int
    {
        return max(1, min(60, (int) self::get('orders_expired_handler_interval_minutes')));
    }

    public static function ordersAutoCancelIntervalMinutes(): int
    {
        return max(1, min(60, (int) self::get('orders_auto_cancel_interval_minutes')));
    }

    public static function minutesCronExpression(int $minutes): string
    {
        $minutes = max(1, min(60, $minutes));

        if ($minutes === 60) {
            return '0 * * * *';
        }

        return "*/{$minutes} * * * *";
    }

    public static function hoursCronExpression(int $hours): string
    {
        $hours = max(1, min(24, $hours));

        if ($hours === 24) {
            return '0 0 * * *';
        }

        return "0 */{$hours} * * *";
    }

    private static function get(string $key): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $value = null;

        foreach (self::candidateKeys($key) as $candidateKey) {
            $value = Setting::query()
                ->where('key', $candidateKey)
                ->value('value');

            if ($value !== null && $value !== '') {
                return self::$cache[$key] = $value;
            }
        }

        if ($key === 'warranty_period_days') {
            $legacyWarranty = NewSettings::query()
                ->where('key', 'warrenty_period')
                ->value('value');

            if ($legacyWarranty !== null && $legacyWarranty !== '') {
                return self::$cache[$key] = $legacyWarranty;
            }
        }

        return self::$cache[$key] = self::DEFAULTS[$key];
    }

    private static function set(string $key, mixed $value): void
    {
        Setting::setValue($key, (string) $value, self::groupFor($key));
        self::$cache[$key] = $value;

        if ($key === 'warranty_period_days') {
            Setting::setValue('warranty_period', (string) $value, 'warranty');
            NewSettings::query()->updateOrCreate(
                ['key' => 'warrenty_period'],
                ['value' => (string) $value]
            );
        }
    }

    private static function candidateKeys(string $key): array
    {
        return match ($key) {
            'warranty_period_days' => ['warranty_period_days', 'warranty_period'],
            default => [$key],
        };
    }

    private static function groupFor(string $key): string
    {
        return match ($key) {
            'warranty_period_days',
            'warranty_status_update_time',
            'warranty_initialize_interval_hours' => 'warranty',
            'order_expiration_hours',
            'order_new_deadline_hours',
            'order_under_process_deadline_hours',
            'order_urgent_threshold_hours',
            'old_pending_orders_days',
            'orders_timer_interval_minutes',
            'orders_expired_handler_interval_minutes',
            'orders_auto_cancel_interval_minutes' => 'orders',
            default => 'automation',
        };
    }
}
