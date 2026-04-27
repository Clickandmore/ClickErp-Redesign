<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Shift;
use Nami\Hr\Services\SanctionService;
use Nami\Hr\Models\ShiftDay;
use Nami\Hr\Models\ShiftRole;
use Illuminate\Support\Facades\Storage;

class ShiftService extends MainService
{
    public function __construct(Shift $model,private SanctionService $sanctionService
    ,private ShiftDayService $shiftDayService,private ShiftRoleService $shiftRoleService)
    {
        $this->model = $model;
        $this->fileFolder = "shifts/";
    }
    public function getShift()
    {
        return $this->get();
    }

    public function getShiftWithNoPermission($ids = null)
    {
        return $this->model->whereDoesntHave('permissions', function ($query) use($ids){
            if(!$ids) return;
            $query->whereNotIn('hr_shifts.id', $ids);
        })->get();
    }

    public function getSanction()
    {
        return $this->sanctionService->getSanctionWithDelays();
    }

    public function getAllSanction()
    {
        return $this->sanctionService->get();
    }


    public function getShiftDay()
    {
        return $this->shiftDayService->getShiftDay();
    }

    public function getShiftRole()
    {
        return $this->shiftRoleService->getShiftRole();
    }

    public function storeShift($data,$request)
    {
        $shift = $this->store($data);

        $days = $request->input('days', []);
        foreach ($days as $day) {
            $shiftDay = $this->getShiftDay();
            $shiftDay->days = $day;
            $shiftDay->shift_id = $shift->id;
            $shiftDay->save();
        }

        $shiftRolesData = $request->input('minutes', []);
        foreach ($shiftRolesData as $key => $shiftRoleData) {
            $shiftRole = $this->getShiftRole();
            $shiftRole->minutes = $shiftRoleData;
            $shiftRole->sanctions_id = $request->sanctions_id[$key];
            $shiftRole->shift_id = $shift->id;
            $shiftRole->save();
        }
        return $shift;
    }

    public function updateShift($id, $data,$request)
    {
        $shift = $this->find($id);
        $shift->update($data);

        $shift->shiftDays()->delete();
        $days = $request->input('days', []);
        foreach ($days as $day) {
            $shiftDay = $this->getShiftDay();
            $shiftDay->days = $day;
            $shiftDay->shift_id = $shift->id;
            $shiftDay->save();
        }

        $shift->shiftRoles()->delete();
        $shiftRolesData = $request->input('minutes', []);
        foreach ($shiftRolesData as $key => $shiftRoleData) {
            $shiftRole = $this->getShiftRole();
            $shiftRole->minutes = $shiftRoleData;
            $shiftRole->sanctions_id = $request->sanctions_id[$key];
            $shiftRole->shift_id = $shift->id;
            $shiftRole->save();
        }

        return $shift;
    }

    public function deleteShift($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
