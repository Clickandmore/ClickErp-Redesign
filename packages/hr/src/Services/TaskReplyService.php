<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\HrEmployeeTask;
use Nami\Hr\Models\HrTaskReply;

class TaskReplyService extends MainService
{

    function __construct()
    {
        $this->model = new HrTaskReply();
        $this->fileFolder = "images/admins/hr_task_replies/";
    }

    public function store($data)
    {
        if (isset($data['file'])){
            $data['file'] = uploadFile($data['file'], $this->fileFolder);
        }
        $task = parent::store($data);
        return $task;
    }

    public function update($id, $data)
    {
        $task = $this->find($id);
        if (isset($data['status'])){
            HrEmployeeTask::where(['task_id'=> $task->task_id, 'employee_id' => $task->employee_id])->update(['status' => $data['status']]);
        }
//        if (isset($data['file'])){
//            $data['file'] = uploadFile($data['file'], $this->fileFolder);
//        }
        return $task;
    }

    public function createData($mainRoute)
    {
       $user_id = auth('admin')->user()->id;
        $data['storeRoute'] = route($mainRoute.'.store');
        $data['employee_id'] = @Employee::where('admin_id', $user_id)->first()->id;
        return $data;
    }

    public function editData($id,$mainRoute)
    {
        $user_id = auth('admin')->user()->id;
        $data['obj'] = $this->find($id);
        $data['updateRoute'] = route($mainRoute.'.store');
        $data['employee_id'] = @Employee::where('admin_id', $user_id)->first()->id;
        return $data;
    }

    public function showData($id)
    {
        $user_id = auth()->user()->id;
        $data['employee_id'] = @Employee::where('user_id', $user_id)->first()->id;
        $data['obj'] = $this->find($id);
        return $data;
    }
}
