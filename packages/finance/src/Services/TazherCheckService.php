<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\BondCheckStatusEnum;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\BondChecks as Model;

class TazherCheckService extends MainService
{
    public function __construct(Model $model,
        protected BondChecksService $bondChecksService,
        protected BondService $bondService,
        protected CatchCheckService $catchCheckService
    )
    {
        $this->model = $model;
        $this->fileFolder = 'bond_checks/';
    }

    public function makeTazher($id,$data)
    {
        $bondCheck = $this->find($id);
        $this->updateBondCheck($bondCheck);
        $this->endMainConstraint($bondCheck);
        $data['type'] = BondTypes::Sarf->value;
        $this->bondService->storeBond($data);
    }

    public function updateBondCheck($bondCheck)
    {
        $bondCheck->update([
            'status' => BondCheckStatusEnum::TazherQabd->value
        ]);
    }

    public function endMainConstraint($bondCheck)
    {
        $mainBond = $bondCheck->bond;
        $statement = "تحصيل شيك بقيمة {$mainBond->net} للشيك رقم {$bondCheck->check_num} رقم السند {$mainBond->code} بتاريخ {$mainBond->date}";
        $con_type  = ConstraintTypiesEnum::CheckCollection;
        $newConstraint = $this->catchCheckService->createNewConstraint($mainBond->constraint_id, date('Y-m-d'), $statement,$con_type);
        $this->catchCheckService->createConstraintDetails($newConstraint, $mainBond);
    }






}
