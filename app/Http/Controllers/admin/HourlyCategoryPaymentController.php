<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyServiceCategory;
use App\Services\Hourly\{HourlyBookingRules, ServiceCategoryService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** تبويب «طرق الدفع»: 3 مفاتيح (الموقع والتطبيق / الموظف / الفرع) لكل طريقة دفع. */
class HourlyCategoryPaymentController extends Controller
{
    public const CHANNELS = ['web_app', 'employee', 'branch'];

    public function update(Request $request, HourlyServiceCategory $category, ServiceCategoryService $service)
    {
        $rules = ['methods' => ['required', 'array']];
        foreach (HourlyBookingRules::ALL_PAYMENT_METHODS as $method) {
            foreach (self::CHANNELS as $channel) $rules["methods.$method.$channel"] = ['nullable', 'boolean'];
        }
        $data = $request->validate($rules);
        $before = $this->current($category);
        DB::transaction(function () use ($category, $data) {
            foreach (HourlyBookingRules::ALL_PAYMENT_METHODS as $method) {
                $flags = [];
                foreach (self::CHANNELS as $channel) {
                    $supported = $channel === 'web_app'
                        ? in_array($method,HourlyBookingRules::PAYMENT_METHODS,true)
                        : $method !== 'wallet';
                    $flags[$channel] = $supported && (bool) ($data['methods'][$method][$channel] ?? false);
                }
                DB::table('hourly_category_payment_methods')->updateOrInsert(
                    ['service_category_id' => $category->id, 'payment_method' => $method],
                    $flags + ['updated_at' => now(), 'created_at' => now()]
                );
            }
        });
        $service->audit($category, 'payments', 'edit', $before, $this->current($category));
        return redirect()->to(route('admin.hourly-service-categories.show', $category).'#payments')->with('success', __('hourly_category_ui.payments_saved'));
    }

    /** حالة كل طريقة في الفئة (غير المسجلة = مسموحة في كل القنوات). */
    public static function matrix(HourlyServiceCategory $category): array
    {
        $rows = DB::table('hourly_category_payment_methods')->where('service_category_id', $category->id)->get()->keyBy('payment_method');
        $matrix = [];
        foreach (HourlyBookingRules::ALL_PAYMENT_METHODS as $method) {
            foreach (self::CHANNELS as $channel) {
                $supported = $channel === 'web_app'
                    ? in_array($method,HourlyBookingRules::PAYMENT_METHODS,true)
                    : $method !== 'wallet';
                $matrix[$method][$channel] = $supported
                    && (isset($rows[$method]) ? (bool) $rows[$method]->{$channel} : $method !== 'wallet');
            }
        }
        return $matrix;
    }

    private function current(HourlyServiceCategory $category): array
    {
        return self::matrix($category);
    }
}
