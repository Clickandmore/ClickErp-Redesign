<?php

namespace Nami\Hr\Services;

use Nami\Hr\Enums\AllowanceTypeis;
use Nami\Hr\MainService;
use Nami\Hr\Models\Allowance;
use Nami\Hr\Models\Job;
use Nami\Hr\Models\Sanction;
use Illuminate\Support\Facades\Storage;

class SanctionService extends MainService
{
    public function __construct(Sanction $model)
    {
        $this->model = $model;
    }


    public function getSanctionTypies()
    {
        return AllowanceTypeis::cases();
    }

    public function getSanction()
    {
        return $this->get();
    }

    public function getSanctionWithDelays()
    {
        return Sanction::where('is_delays',1)->get();
    }

    public function storeSanction($data,$request)
    {
        $data['is_delays'] = ($request->has('is_delays') && $request->input('is_delays') == 1) ? 1 : 0;
        return $this->store( $data);
    }

    public function updateSanction($id, $data,$request)
    {
        $data['is_delays'] = ($request->has('is_delays') && $request->input('is_delays') == 1) ? 1 : 0;
        $this->find($id)->update( $data);
    }

    public function deleteSanction($id)
    {
        $obj = $this->find($id);
        $this->delete($id);
    }

}
