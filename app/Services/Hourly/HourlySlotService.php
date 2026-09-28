<?php
namespace App\Services\Hourly;

use App\Support\HourlyRentalSettings;
use Carbon\Carbon;

/** Operating slots only; worker availability is checked during assignment in phase two. */
class HourlySlotService
{
    /**
     * مواعيد التشغيل بعد تطبيق قواعد الفئة عبر HourlyBookingRules:
     * نافذة التقويم وأيام العمل ومهلة اليوم، ثم الإغلاقات المؤقتة.
     */
    public function forDate(string $date, float $hours, ?int $notice = null, array $item = [], bool $dispatcher = false,bool $ignoreHourCaps=false,bool $ignoreNotice=false): array
    {
        HourlyOrderService::enabled();
        $rules = app(HourlyBookingRules::class);
        $window = $rules->calendarWindow($item, $date, $dispatcher);
        if ($window === null) return [];
        $slots = $this->operatingSlots($date, $hours, $notice, $item, $dispatcher, $ignoreNotice || $window['ignore_notice'],$ignoreHourCaps);
        return $rules->withoutClosures($item, $rules->withBuffer($slots, $ignoreNotice ? 0 : $window['buffer_hours']));
    }

    private function operatingSlots(string $date, float $hours, ?int $notice, array $item, bool $dispatcher, bool $ignoreNotice,bool $ignoreHourCaps=false): array
    {
        $settings = HourlyRentalSettings::all();
        // أيام الإغلاق العامة؛ عناصر الفئات تتبع أيام عمل الفئة (التي ترث هذه الأيام) في calendarWindow.
        if (empty($item['service_category_id']) && empty($item['ignore_closed_days']) && in_array(Carbon::parse($date)->dayOfWeek, $settings['closed_weekdays'], true)) return [];
        if ($hours <= 0 || (!$ignoreHourCaps && $hours > HourlyRentalSettings::effectiveHourCaps()['per_day'])) return [];
        $start = Carbon::parse($date.' '.$settings['working_start']);
        $end = Carbon::parse($date.' '.$settings['working_end']);
        $earliest = $ignoreNotice ? Carbon::parse($date)->startOfDay() : now()->addHours($notice ?? $settings['min_notice_hours']);
        $minutes = (int) round($hours * 60);
        $minutes = (int) ($item['duration_minutes'] ?? $minutes);
        $slots = [];
        if (isset($item['start_times'])) {
            foreach ($item['start_times'] as $time => $access) {
                if ($access !== 'all' && !($dispatcher && $access === 'dispatch')) continue;
                $cursor = Carbon::parse($date.' '.$time);
                if ($cursor->gte($earliest)) $slots[] = ['starts_at'=>$cursor->format('Y-m-d H:i:s'), 'ends_at'=>$cursor->copy()->addMinutes($minutes)->format('Y-m-d H:i:s')];
            }
            return $slots;
        }
        for ($cursor = $start; $cursor->copy()->addMinutes($minutes)->lte($end); $cursor->addMinutes($settings['slot_minutes'])) {
            if ($cursor->gte($earliest)) $slots[] = ['starts_at'=>$cursor->format('Y-m-d H:i:s'), 'ends_at'=>$cursor->copy()->addMinutes($minutes)->format('Y-m-d H:i:s')];
        }
        return $slots;
    }

    public function validate(array $dates, float $hours, int $notice, Carbon $expires, array $item = [], bool $dispatcher = false,bool $ignoreHourCaps=false,bool $ignoreNotice=false): array
    {
        $result = [];
        foreach ($dates as $date) {
            $start = Carbon::createFromFormat('Y-m-d H:i', $date)->second(0);
            $end=$start->copy()->addMinutes((int)($item['duration_minutes']??round($hours*60)));
            if($end->gt($expires))HourlyOrderService::reject('الباقة صالحة حتى '.$expires->format('d/m/Y H:i').'؛ يجب أن تنتهي الزيارة قبل هذا الموعد.');
            $slot = collect($this->forDate($start->toDateString(), $hours, $notice, $item, $dispatcher,$ignoreHourCaps,$ignoreNotice))->firstWhere('starts_at', $start->format('Y-m-d H:i:s'));
            if(!$slot){
                $withoutNotice=collect($this->forDate($start->toDateString(),$hours,$notice,$item,$dispatcher,$ignoreHourCaps,true))->firstWhere('starts_at',$start->format('Y-m-d H:i:s'));
                if($withoutNotice && !$ignoreNotice){
                    $window=app(HourlyBookingRules::class)->calendarWindow($item,$start->toDateString(),$dispatcher);
                    $minimum=max($notice,(int)($window['buffer_hours']??0));
                    HourlyOrderService::reject('أقل مهلة حجز '.$minimum.' ساعة؛ أقرب موعد مسموح بعد '.now()->addHours($minimum)->format('d/m/Y H:i').'.');
                }
                $settings=HourlyRentalSettings::all();
                $allowed=isset($item['start_times'])?implode('، ',array_keys(array_filter($item['start_times'],fn($access)=>$access==='all'||($dispatcher && $access==='dispatch')))):$settings['working_start'].'–'.$settings['working_end'].'، بفاصل '.$settings['slot_minutes'].' دقيقة';
                HourlyOrderService::reject('الموعد خارج فترات العمل المتاحة. فترة العمل / أوقات البدء المسموحة: '.$allowed.'؛ يجب أن يلائم الموعد والمدة أيام العمل ونافذة التقويم والإغلاقات وحد الساعات.');
            }
            $rest = (int) ($item['break_minutes'] ?? 0) + (int) ($item['buffer_minutes'] ?? 0);
            foreach ($result as $existing) if (Carbon::parse($slot['starts_at'])->lt(Carbon::parse($existing['ends_at'])->addMinutes($rest)) && Carbon::parse($slot['ends_at'])->addMinutes($rest)->gt(Carbon::parse($existing['starts_at']))) HourlyOrderService::reject('مواعيد الزيارات متداخلة مع وقت الزيارة أو الاستراحة أو الفاصل.');
            $result[] = $slot;
        }
        return $result;
    }
}
