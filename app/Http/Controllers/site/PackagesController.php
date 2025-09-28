<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\PackageAction;
use App\Http\Actions\ingaz\UserAddressAction;
use App\Http\Actions\sharedCode\CountriesAction;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Contracts\Queue\Job;

class PackagesController extends Controller
{
    public function show($id, MaidAction $action, PackageAction $packageAction,
                         UserAddressAction $userAddressAction, JobTypeAction $jobTypeAction,)
    {
        $data['maid'] = $action->find($id);
        $data['packages'] = $packageAction->getWhere(['nationality_id' => $data['maid']->nationality_id]);
        $data['addresses'] = $userAddressAction->getwhere(['user_id' => getGuardedUser('id')]);
        $data['type'] = $jobTypeAction->find(2);
        $data['countries'] = Country::all();
        return view('site.pages.packages', $data);
    }


}
