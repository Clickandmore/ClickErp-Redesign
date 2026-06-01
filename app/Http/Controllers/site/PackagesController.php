<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\PackageAction;
use App\Http\Actions\ingaz\UserAddressAction;
use App\Http\Controllers\admin\Controller;
use App\Models\Country;

class PackagesController extends Controller
{
    public function show($id, MaidAction $action, PackageAction $packageAction,
                         UserAddressAction $userAddressAction, JobTypeAction $jobTypeAction,)
    {
        $data['maid'] = $action->find($id);
        $data['packages'] = \App\Models\ingaz\Package::with('translations')
            ->visible()
            ->forNationality($data['maid']->nationality_id)
            ->ordered()
            ->get();
        $data['addresses'] = $userAddressAction->getwhere(['user_id' => getGuardedUser('id')]);
        $data['type'] = $jobTypeAction->find(2);
        $data['countries'] = Country::all();
        return view('site.pages.packages', $data);
    }


}
