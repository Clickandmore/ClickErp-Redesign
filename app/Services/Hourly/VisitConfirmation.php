<?php

namespace App\Services\Hourly;

use Illuminate\Support\Facades\Schema;

/**
 * من يؤكد وصول الطاقم إلى العميل (arrival) ومن يؤكد عودته إلى الإيواء (return) في كل زيارة.
 *
 * auto (الافتراضي): سائق المهمة إن كان معيّنًا، وإلا العاملة.
 * driver / worker / both: اختيار صريح من الإدارة؛ both يعني أن أول من يؤكد منهما يكفي.
 * الإدارة تستطيع دائمًا التأكيد يدويًا من شاشة الزيارات بغض النظر عن هذا الإعداد.
 */
class VisitConfirmation
{
    public const ARRIVAL = 'arrival';
    public const RETURN = 'return';

    public const MODES = [
        'auto' => 'تلقائي — السائق إن كان معيّنًا وإلا العاملة',
        'driver' => 'السائق فقط',
        'worker' => 'العاملة فقط',
        'both' => 'السائق أو العاملة',
    ];

    public static function ready(): bool
    {
        return Schema::hasColumn('hourly_visits', 'arrival_confirmer') && Schema::hasColumn('hourly_visits', 'return_confirmer');
    }

    /** الإعداد المخزَّن على الزيارة (auto عند غيابه). يقبل نموذج الزيارة أو صفًا خامًا من الاستعلام. */
    public static function mode(object $visit, string $step): string
    {
        $mode = $visit->{$step.'_confirmer'} ?? 'auto';
        return isset(self::MODES[$mode]) ? $mode : 'auto';
    }

    /** المؤكِّد الفعلي بعد حل «تلقائي»: driver | worker | both. */
    public static function effective(object $visit, string $step): string
    {
        $mode = self::mode($visit, $step);
        if ($mode !== 'auto') return $mode;
        return !empty($visit->{self::driverColumn($step)}) ? 'driver' : 'worker';
    }

    public static function allowsDriver(object $visit, string $step): bool
    {
        return self::effective($visit, $step) !== 'worker';
    }

    public static function allowsWorker(object $visit, string $step): bool
    {
        return self::effective($visit, $step) !== 'driver';
    }

    /** سبب منع السائق من التأكيد، أو null إن كان مسموحًا. */
    public static function driverBlockReason(object $visit, string $step): ?string
    {
        if (self::allowsDriver($visit, $step)) return null;
        return $step === self::ARRIVAL
            ? 'تأكيد الوصول في هذه الزيارة تقوم به العاملة من تطبيقها.'
            : 'تأكيد العودة للإيواء في هذه الزيارة تقوم به العاملة من تطبيقها.';
    }

    /** سبب منع العاملة من التأكيد، أو null إن كان مسموحًا. */
    public static function workerBlockReason(object $visit, string $step): ?string
    {
        if (self::allowsWorker($visit, $step)) return null;
        $assigned = !empty($visit->{self::driverColumn($step)});
        if ($step === self::ARRIVAL) {
            return $assigned ? 'وصول الطاقم يُسجّل بواسطة السائق عند التسليم للعميل.'
                : 'تأكيد الوصول في هذه الزيارة مُسند للسائق؛ يجب تعيين سائق التوصيل أولًا.';
        }
        return $assigned ? 'تأكيد العودة للإيواء في هذه الزيارة يقوم به سائق الاستلام.'
            : 'تأكيد العودة للإيواء في هذه الزيارة مُسند للسائق؛ راجعي الإدارة لتعيين سائق الاستلام أو تأكيد العودة.';
    }

    public static function assertDriver(object $visit, string $step): void
    {
        if ($reason = self::driverBlockReason($visit, $step)) HourlyOrderService::reject($reason);
    }

    public static function assertWorker(object $visit, string $step): void
    {
        if ($reason = self::workerBlockReason($visit, $step)) HourlyOrderService::reject($reason);
    }

    /** ما يرسله تطبيقا العاملة والسائق: الإعداد المخزَّن ومن يؤكد فعليًا. */
    public static function payload(object $visit): array
    {
        $out = [];
        foreach ([self::ARRIVAL, self::RETURN] as $step) {
            $out[$step] = ['mode' => self::mode($visit, $step), 'by' => self::effective($visit, $step)];
        }
        return $out;
    }

    private static function driverColumn(string $step): string
    {
        return $step === self::ARRIVAL ? 'delivery_driver_id' : 'pickup_driver_id';
    }
}
