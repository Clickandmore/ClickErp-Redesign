<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\EmployeeReward;
use Nami\Hr\Services\RewardService;
use Nami\Hr\Enums\ExchangeTimingEnum;
use Nami\Hr\Services\EmployeeService;

class EmployeeRewardService extends MainService
{
    public function __construct(EmployeeReward $model,
    private EmployeeService $employeeService,
    private RewardService $rewardService)
    {
        $this->model = $model;
    }

    public function getDataTable($user_id = null)
    {
//        if($user_id == null){
//            $user_id =  @auth('admin')->user()->employee->id;
//        }
//        return parent::getDataTable()->where(['employee_id' => $user_id]);
        return parent::getDataTable();
    }

    public function getEmployees() {
        return $this->employeeService->get();
    }

    public function getRewards() {
        return $this->rewardService->get();
    }

    public function storeEmployeeReward($data)
    {
        if ($data['exchange_timing'] == \Nami\Hr\Enums\ExchangeTimingEnum::WithSalary->value) {
            $yearMonth = $data['date'];
            $date = new \DateTime($yearMonth . '-01');
            $date->modify('last day of this month');
            $data['date'] = $date->format('Y-m-d');
        }
        return $this->store($data);
    }

    public function updateEmployeeReward($id, $data)
    {
        if ($data['exchange_timing'] == \Nami\Hr\Enums\ExchangeTimingEnum::WithSalary->value) {
            $yearMonth = $data['date'];
            $date = new \DateTime($yearMonth . '-01');
            $date->modify('last day of this month');
            $data['date'] = $date->format('Y-m-d');
        }
        return $this->update($id, $data);
    }


}
