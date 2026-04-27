<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\Permission;
use Illuminate\Support\Facades\Storage;

class PermissionService extends MainService
{
    public function __construct(Permission $model)
    {
        $this->model = $model;
    }

    public function getPermission()
    {
        return $this->get();
    }


    public function storePermission($data,$request)
    {
        return $this->store( $data);
    }

    public function updatePermission($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deletePermission($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
