<?php

namespace Nami\Hr\Services;



use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeShift;
use Nami\Hr\Models\Job;
use Illuminate\Support\Facades\Storage;

class JobService extends MainService
{
    public function __construct(Job $model)
    {
        $this->model = $model;
    }

    public function getJob()
    {
        return $this->get();
    }


    public function storeJob($data,$request)
    {
        return $this->store( $data);
    }

    public function updateJob($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deleteJob($id)
    {
        $obj = $this->find($id);
        $checkDelete = Employee::where('job_id', $obj->id)->count();
        if ($checkDelete > 0) {
            return false;
        }
        return $this->delete($id);
    }

}
