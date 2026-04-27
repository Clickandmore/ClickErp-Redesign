<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeePermission;
use Nami\Hr\Models\EmployeeAttendances;
use Illuminate\Support\Facades\Storage;
use Nami\Hr\Enums\EmployeeVacationStatus;
use Nami\Hr\Models\PermissionSetting;
use Nami\Hr\Models\PermissionShift;
use Yajra\DataTables\Exceptions\Exception;

class EmployeePermissionService extends MainService
{
    public function __construct(EmployeePermission $model,private EmployeeService $employeeService
    ,private PermissionService $permissionService, protected EmployeeShiftService $employeeShiftService)
    {
        $this->model = $model;
        $this->fileFolder = "images/admins/permissions/";
        $this->files = ['file'];
    }
    public function getDataTable($user_id = null)
    {
        if($user_id == null){
            $user_id =  @auth('admin')->user()->employee->id;
        }
        return parent::getDataTable()->where('employee_id', $user_id);
    }

    public function getEmployee()
    {
        return $this->employeeService->getEmployee();
    }

    public function getPermission()
    {
        return $this->permissionService->getPermission();
    }

    public function getEmployeePermissionStatus()
    {
        return EmployeeVacationStatus::cases();
    }

    public function storeEmployeePermission($data,$request)
    {
        $attendance = EmployeeAttendances::where('employee_id', $request->employee_id)
                            ->where('date', $request->date)
                            ->first();
        if ($attendance && $attendance->exists) {
            $existShift = $this->existShift($request);
            if (!$existShift) {
                return ['code' => 402,'data' => null,'message' => __("hr::hr.this employee does not have a shift")];
            }

            $permissionShift = $this->getShiftPermission($existShift->shift_id);
            if (!$permissionShift) {
                return ['code' => 402,'data' => null,'message' => __("hr::hr.this employee does not have a permission shift")];
            }
            $permissionSetting = $this->getPermissionSetting($permissionShift->permission_id);
            $employeePermissionCount = $this->getEmployeePermission($request->employee_id);
            if($employeePermissionCount->count() > 0) {
                if ($permissionSetting->per_month <= $employeePermissionCount->count()) {
                    return ['code' => 402,'data' => null,'message' => __("hr::hr.permission_request_exceed", ['count' => $permissionSetting->per_month])];
                }

                if ($permissionSetting->number_of_hours * 60 <= $employeePermissionCount->sum('time')) {
                    return ['code' => 402,'data' => null,'message' => __("hr::hr.permission_request_exceed_hours", ['hours' => $permissionSetting->number_of_hours * 60])];
                }
            }
            $attendance->have_permission = 1;
            $attendance->save();

            $empPermission = $this->store($data);
            return ['code' => 200,'data' => $empPermission,'message' =>null];
        }
            return ['code' => 402,'data' => null,'message' => __("hr::hr.no_permission_without_attendance")];
    }

    public function existShift($request) {
        $shift = $this->employeeShiftService->getWhereFirst(['employee_id' => $request->employee_id]);
        if (!isset($shift)) {
            return false;
        }
        return $shift;
    }

    public function getShiftPermission($shift_id) {
        $permissionShift = PermissionShift::where('shift_id', $shift_id)->first();
        if (!isset($permissionShift)) {
            return false;
        }
        return $permissionShift;
    }


    public function getPermissionSetting($permission_setting_id) {
        $permissionSetting = PermissionSetting::where('id', $permission_setting_id)->first();
        if (!isset($permissionSetting)) {
            return false;
        }
        return $permissionSetting;
    }

    public function getEmployeePermission($employee_id) {
        return EmployeePermission::where('employee_id', $employee_id)->where('status', '!=', 0)->whereMonth('date', now()->month);
    }



    public function updateEmployeePermission($id, $data, $request)
    {
        $attendance = EmployeeAttendances::where('employee_id', $request->employee_id)
                            ->where('date', $request->date)
                            ->first();

        if ($attendance && $attendance->exists) {
            $existShift = $this->existShift($request);
            if (!$existShift) {
                return ['code' => 402, 'data' => null, 'message' => __("hr::hr.this employee does not have a shift")];
            }

            $permissionShift = $this->getShiftPermission($existShift->shift_id);
            if (!$permissionShift) {
                return ['code' => 402, 'data' => null, 'message' => __("hr::hr.this employee does not have a permission shift")];
            }

            $permissionSetting = $this->getPermissionSetting($permissionShift->permission_id);

            $employeePermissionCount = $this->getEmployeePermission($request->employee_id);
            if ($employeePermissionCount->count() > 0) {
                if ($permissionSetting->per_month <= $employeePermissionCount->count()) {
                    return ['code' => 402, 'data' => null, 'message' => __("hr::hr.permission_request_exceed", ['count' => $permissionSetting->per_month])];
                }

                if ($permissionSetting->number_of_hours * 60 <= $employeePermissionCount->sum('time')) {
                    return ['code' => 402, 'data' => null, 'message' => __("hr::hr.permission_request_exceed_hours", ['hours' => $permissionSetting->number_of_hours * 60])];
                }
            }

            $oldPermission = $this->find($id);
            $oldAttendance = EmployeeAttendances::where('employee_id', $request->employee_id)
                                ->where('date', $oldPermission->date)
                                ->first();

            if ($oldAttendance) {
                $oldAttendance->have_permission = 0;
                $oldAttendance->save();
            }

            // تحديث الإذن
            $empPermission = $this->update($id, $data);

            // تحديث حالة الحضور المرتبط بالإذن الجديد
            $attendance->have_permission = 1;
            $attendance->save();

            return ['code' => 200, 'data' => $empPermission, 'message' => null];
        }

        return ['code' => 402, 'data' => null, 'message' => __("hr::hr.no_permission_without_attendance")];
    }

    public function deleteEmployeePermission($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
