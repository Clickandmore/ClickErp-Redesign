<?php

namespace Tests\Unit;

use App\Http\Controllers\Hourly\StaffScheduleController;
use App\Models\Hourly\HourlyVisit;
use App\Models\ingaz\Maid;
use App\Support\HourlyScheduleSettings;
use Carbon\Carbon;
use Tests\HourlyTestCase as TestCase;

class HourlyStaffScheduleTest extends TestCase
{
    public function test_settings_are_clamped_and_copy_fields_keep_order_without_unknown_or_missing_keys(): void
    {
        $settings = HourlyScheduleSettings::normalize([
            'start_time' => '06:00', 'end_time' => '24:00', 'row_height' => 99,
            'copy_fields' => [['key' => 'price', 'enabled' => '1', 'label' => '0', 'blank' => '1'], ['key' => 'hack'], ['key' => 'price', 'enabled' => false], ['key' => 'customer', 'enabled' => true]],
        ]);

        $this->assertSame(['06:00', '24:00', 30], [$settings['start_time'], $settings['end_time'], $settings['row_height']]);
        $keys = array_column($settings['copy_fields'], 'key');
        // الترتيب المحفوظ أولًا، بلا مفاتيح مجهولة أو مكررة، ثم باقي الحقول معطّلة.
        $this->assertSame(['price', 'customer'], array_slice($keys, 0, 2));
        $this->assertEqualsCanonicalizing(array_keys(HourlyScheduleSettings::COPY_FIELDS), $keys);
        $this->assertSame(['key' => 'price', 'enabled' => true, 'label' => false, 'blank' => true], $settings['copy_fields'][0]);
        $this->assertFalse($settings['copy_fields'][2]['enabled']);

        // النهاية قبل البداية أو صيغة غير ساعة كاملة ترجع للافتراضي.
        $fallback = HourlyScheduleSettings::normalize(['start_time' => '20:00', 'end_time' => '09:00', 'row_height' => 3]);
        $this->assertSame(['07:00', '23:00', 10], [$fallback['start_time'], $fallback['end_time'], $fallback['row_height']]);
        $this->assertSame('07:00', HourlyScheduleSettings::normalize(['start_time' => '07:30'])['start_time']);
    }

    public function test_blocks_are_positioned_in_minutes_and_overlaps_get_side_lanes(): void
    {
        $date = Carbon::parse('2026-09-22');
        $maid = (new Maid())->forceFill(['id' => 5, 'name' => 'Sara']);
        $visit = function (int $id, string $from, string $to) use ($date, $maid) {
            $visit = (new HourlyVisit())->forceFill(['id' => $id, 'scheduled_start_at' => $date->copy()->setTimeFromTimeString($from), 'scheduled_end_at' => $date->copy()->setTimeFromTimeString($to), 'planned_hours' => 1]);
            return $visit->setRelation('workers', collect([$maid]));
        };
        $visits = collect([$visit(1, '08:00', '12:00'), $visit(2, '11:00', '13:00'), $visit(3, '13:00', '14:00'), $visit(4, '05:00', '07:30')]);

        $method = new \ReflectionMethod(StaffScheduleController::class, 'blocks');
        $method->setAccessible(true);
        $blocks = $method->invoke(new StaffScheduleController(), $visits, collect([5]), $date, ['start_time' => '07:00', 'end_time' => '23:00'])[5];
        $byVisit = collect($blocks)->keyBy('visit');

        $this->assertSame(['top' => 60, 'height' => 240, 'lane' => 0], collect($byVisit[1])->only(['top', 'height', 'lane'])->all());
        $this->assertSame(1, $byVisit[2]['lane']); // يتداخل مع الأولى فيأخذ مسارًا جانبيًا
        $this->assertSame(0, $byVisit[3]['lane']); // يبدأ عند انتهاء الأولى فيعود للمسار الأول
        $this->assertSame(['top' => 0, 'height' => 30], collect($byVisit[4])->only(['top', 'height'])->all()); // يُقصّ عند بداية التقويم
        $this->assertSame(2, $byVisit[1]['lanes']);
    }
}
