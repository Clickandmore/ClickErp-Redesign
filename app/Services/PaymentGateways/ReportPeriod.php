<?php

namespace App\Services\PaymentGateways;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * فترة تقارير بوابات الدفع: شهر محدد (الافتراضي الشهر الحالي) أو مدى من/إلى،
 * ومعها الأشهر الاثنا عشر المنتهية بنهاية الفترة لرسم الاتجاه.
 */
class ReportPeriod
{
    public const TREND_MONTHS = 12;

    private function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly string $mode,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $from = self::date($request->input('from'));
        $to = self::date($request->input('to'));
        if ($from && $to) {
            [$from, $to] = $from->lte($to) ? [$from, $to] : [$to, $from];

            return new self($from->startOfDay(), $to->endOfDay(), 'range');
        }

        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->input('month')) ? $request->input('month') : now()->format('Y-m');
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        return new self($start, $start->endOfMonth(), 'month');
    }

    public function month(): string
    {
        return $this->from->format('Y-m');
    }

    public function label(): string
    {
        return $this->mode === 'month'
            ? $this->from->locale(app()->getLocale())->translatedFormat('F Y')
            : $this->from->format('Y-m-d') . ' — ' . $this->to->format('Y-m-d');
    }

    /** بداية نافذة الاتجاه: أول يوم قبل 11 شهراً من شهر نهاية الفترة. */
    public function trendStart(): CarbonImmutable
    {
        return $this->to->startOfMonth()->subMonths(self::TREND_MONTHS - 1);
    }

    /** @return array<int, string> أشهر الاتجاه بصيغة Y-m من الأقدم للأحدث */
    public function trendMonths(): array
    {
        $months = [];
        for ($month = $this->trendStart(); $month->lte($this->to); $month = $month->addMonth()) {
            $months[] = $month->format('Y-m');
        }

        return $months;
    }

    /** @return array<int, string> أيام الفترة بصيغة Y-m-d (للرسم اليومي؛ حتى 92 يوماً) */
    public function days(): array
    {
        $days = [];
        $last = $this->to->startOfDay();
        for ($day = $this->from->startOfDay(); $day->lte($last) && count($days) < 92; $day = $day->addDay()) {
            $days[] = $day->format('Y-m-d');
        }

        return $days;
    }

    public function contains($date): bool
    {
        if (!$date) {
            return false;
        }
        $date = $date instanceof \DateTimeInterface ? CarbonImmutable::instance($date) : CarbonImmutable::parse($date);

        return $date->betweenIncluded($this->from, $this->to);
    }

    public static function monthLabel(string $month): string
    {
        return Carbon::createFromFormat('Y-m-d', $month . '-01')->locale(app()->getLocale())->translatedFormat('M Y');
    }

    private static function date($value): ?CarbonImmutable
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
