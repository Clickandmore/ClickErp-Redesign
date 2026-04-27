<?php

namespace Nami\Hr\Services;


use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeeVacation;
use Nami\Hr\Models\EmployeeVacationBalance;
use Nami\Hr\Models\Job;

class ToggleStatusEmployeeVacationService extends MainService
{
    public function __construct(
        Job                         $model,
        private VacationYearService $vacationYearService,
    )
    {
        $this->model = $model;
        $this->fileFolder = "jobs/";
    }

    public function getJob()
    {
        return $this->get();
    }


    public function storeJob($data, $request)
    {
        return $this->store($data);
    }

    public function updateToggle($id, $request)
    {
        $newStatus = $request->input('status');
        $vacation_year_id = $this->vacationYearService->getActiveYear()->id ?? null;

        ##### تحديث حالة طلب الاجازة ######
        $orderVacation = EmployeeVacation::findOrFail($id);
        $orderVacation->update(['status' => $newStatus]);
        #################################################

        // لو تم قبول طلب الاجازة قلل من الايام المتبقية لنوع الاجازة لهذا الموظف
        if ($newStatus == 1) {
            $employeeVacationBalance = EmployeeVacationBalance::where('employee_id', $orderVacation->employee_id)
                ->where('vacation_id', $orderVacation->vacation_id)
                ->where('vacation_year_id', $vacation_year_id)
                ->first();
            if ($employeeVacationBalance) {
                $employeeVacationBalance->increment('token_days', $orderVacation->days);
                $employeeVacationBalance->decrement('remain_days', $orderVacation->days);
            } else {
                EmployeeVacationBalance::create(
                    [
                        'employee_id' => $orderVacation->employee_id,
                        'vacation_id' => $orderVacation->vacation_id,
                        'total_days' => $orderVacation->value,
                        'token_days' => $orderVacation->days,
                        'remain_days' => $orderVacation->value - $orderVacation->days,
                        'vacation_year_id' => $vacation_year_id,
                    ]);
            }
        }


    }

    public function deleteJob($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
