<?php

namespace Nami\Hr\Services;


use Nami\Hr\MainService;
use Nami\Hr\Models\PermissionSetting;
use Illuminate\Support\Facades\Storage;

class PermissionSettingService extends MainService
{
    public function __construct(PermissionSetting $model, private ShiftService $shiftService)
    {
        $this->model = $model;
        $this->fileFolder = "permissions/";
    }

    public function getShifts($ids = null)
    {
        return $this->shiftService->getShiftWithNoPermission($ids);
    }

    public function getPermissionSetting() {
        $PermissionSetting = $this->first();
        return $PermissionSetting;
    }

    public function storePermissionSetting($data)
    {
        $PermissionSetting = $this->store($data);
        $PermissionSetting->shifts()->sync($data['shifts_ids']);
        return $PermissionSetting;
    }

    public function updatePermissionSetting($id, $data)
    {
        $this->find($id)->update($data);
        $PermissionSetting =  $this->find($id);
        $PermissionSetting->shifts()->sync($data['shifts_ids']);
        return $PermissionSetting;
    }

    public function deletePermissionSetting($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
