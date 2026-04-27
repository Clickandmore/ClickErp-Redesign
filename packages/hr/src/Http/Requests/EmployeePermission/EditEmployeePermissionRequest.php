<?php

namespace Nami\Hr\Http\Requests\EmployeePermission;

use App\Http\Requests\MainRequest;
use Nami\Hr\Models\PermissionSetting;
use Nami\Hr\Models\EmployeePermission;

class EditEmployeePermissionRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $permissionSetting = PermissionSetting::first();

        $maxPerMonth = $permissionSetting->per_month;
        $maxMinutesPerMonth = $permissionSetting->number_of_hours * 60;

        return [
            'employee_id' => [
                'required',
                function ($attribute, $value, $fail) use ($maxPerMonth, $maxMinutesPerMonth) {
                    $count = EmployeePermission::where('employee_id', $value)
                    ->whereMonth('date', now()->month)
                    ->whereNotIn('id', [request()->request_id])
                    ->count();
                    if ($count >= $maxPerMonth) {
                        $fail("لا يمكن للموظف تقديم طلب إذن أكثر من {$maxPerMonth} مرات في الشهر الواحد.");
                    }

                    $totalMinutes = EmployeePermission::where('employee_id', $value)
                                        ->whereMonth('date', now()->month)
                                        ->whereNotIn('id', [request()->request_id])
                                        ->sum('time');
                    if (($totalMinutes + request()->time) > $maxMinutesPerMonth) {
                        $fail("تم تخطي عدد الدقائق المسموح بها في الشهر {$maxMinutesPerMonth} دقيقة");
                    }
                },
            ],
            'file' => 'nullable',
            'permission_id' => 'required',
            'date' => 'required|date',
            'time' => [
                'required',
                function ($attribute, $value, $fail) use ($permissionSetting) {
                    $maxTime = $permissionSetting->number_of_hours * 60;
                    if ($value > $maxTime) {
                        $fail("يجب أن لا يزيد عدد الدقائق عن عدد الدقائق المسموح به {$maxTime} دقيقة.");
                    }
                },
                function ($attribute, $value, $fail) {
                    $maxTime = 24;
                    if ($value > $maxTime * 60) {
                        $fail("يجب ألا يتجاوز عدد الساعات {$maxTime} ساعة في اليوم.");
                    }
                }
            ],
            'reason' => 'required',
        ];
    }
}
