<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Complaint;
use Nami\Hr\Models\ComplaintReply;
use Nami\Hr\Models\Employee;

class ComplaintReplyService extends MainService
{
    public function __construct()
    {
        $this->model = new ComplaintReply();
    }


    public function getDataTable()
    {
        return parent::getDataTable()->where('complaint_id', request()->input('complaint_id'));
    }


    public function createData($mainRoute)
    {
        return [
            'complaint_id' => request()->input('complaint_id'),
            'complaint' => Complaint::find(request()->input('complaint_id')),
            'employees' => Employee::get(),
            'storeRoute' =>  route($mainRoute.'.store')
        ];
    }

    public function editData($id,$mainRoute)
    {
        $data['obj'] = $this->find($id);
        $data['employees'] = Employee::get();
        $data['updateRoute'] = route($mainRoute.'.update',$id);
        return $data;
    }





}
