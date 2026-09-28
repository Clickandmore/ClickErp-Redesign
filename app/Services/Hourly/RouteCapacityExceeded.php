<?php

namespace App\Services\Hourly;

/**
 * التحويل يتجاوز مقاعد مركبة السائق (vehicle_capacity) في يوم ما.
 * لا يُنفَّذ شيء؛ الواجهة تعرض الأرقام وتعيد الإرسال بـ confirm_capacity عند التأكيد.
 */
class RouteCapacityExceeded extends \RuntimeException
{
    public function __construct(public string $driver, public string $date, public int $assigned, public int $seats)
    {
        parent::__construct(__('hourly_routing.messages.capacity', ['driver' => $driver, 'date' => $date, 'assigned' => $assigned, 'seats' => $seats]));
    }
}
