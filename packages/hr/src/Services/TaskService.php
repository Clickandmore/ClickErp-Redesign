<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\HrEmployeeTask;
use Nami\Hr\Models\HrTask;

class TaskService extends MainService
{

    function __construct()
    {
        $this->model = new HrTask();
        $this->fileFolder = "images/admins/hr_tasks/";
    }


    public function createData($mainRoute)
    {
        $data['employees'] = @Employee::get();
        $data['storeRoute'] = route($mainRoute.'.store');
        return $data;
    }

    public function editData($id,$mainRoute)
    {
        $data['obj'] = $this->find($id);
        $data['employees'] = @Employee::get();
        $data['employee_tasks'] = HrEmployeeTask::where('task_id', $id)->pluck('employee_id')->toArray();

        $data['updateRoute'] = route($mainRoute.'.update', $id);
        return $data;
    }

    public function getMyDataTable($user_id = null)
    {
        if($user_id == null){
            $user_id = @auth('admin')->user()->employee->id;
        }

        // $user_id = @auth('admin')->user()->id;
        // $employee = @Employee::where('admin_id', $user_id)->first()->id;
        $tasks_ids = @HrEmployeeTask::where('employee_id', $user_id)->pluck('task_id')->toArray();

        return $this->model->whereIn('id', $tasks_ids)->latest();
    }

    public function getDataTable()
    {
        return parent::getDataTable();
    }

    public function store($data)
    {
        if (isset($data['file'])){
            $data['file'] = uploadFile($data['file'], $this->fileFolder);
        }
        $task = parent::store($data);
        $task->employees()->sync($data['employee_ids']);
        return $task;
    }

    public function update($id, $data)
    {
        $task = $this->find($id);
        $task->employees()->sync($data['employee_ids']);
        unset($data['employee_ids']);
        if (isset($data['file'])){
            $data['file'] = uploadFile($data['file'], $this->fileFolder);
        }
        return parent::update($id, $data);
    }

    public function showData($id)
    {

        $data['obj'] = $this->find($id);
        return $data;
    }

}
