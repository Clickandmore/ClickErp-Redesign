<?php

namespace Nami\Hr\Services;

use Nami\Hr\Enums\AllowanceTypeis;
use Nami\Hr\MainService;
use Nami\Hr\Models\Reward;
use Illuminate\Support\Facades\Storage;

class RewardService extends MainService
{
    public function __construct(Reward $model)
    {
        $this->model = $model;
        $this->fileFolder = "rewards/";
    }

    public function getRewardTypies()
    {
        return AllowanceTypeis::cases();
    }

    public function storeReward($data,$request)
    {
        return $this->store( $data);
    }

    public function updateReward($id, $data)
    {
        $this->find($id)->update( $data);
    }

    public function deleteReward($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
