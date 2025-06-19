<?php

namespace App\Http\Controllers\Web;

use App\Enums\auth\AdminTypes;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\Package;
use App\Models\ingaz\Religion;
use App\Models\ingaz\UserAddress;
use App\Models\NewSettings;
use Illuminate\Http\Request;

class MaidController extends Controller
{
    public function index(Request $request, MaidAction $action, JobTypeAction $jobTypeAction)
    {
        $data['maids'] = $action->getMaids($request)
            ->where('status', \App\Models\ingaz\Maid::STATUS_AVAILABLE['en']);
        $reservedMaids = Order::whereNotIn('status', ['done', 'refused'])->pluck('maid_id');
        $data['maids'] = $data['maids']->whereNotIn('id', $reservedMaids)->paginate(20);

        $data['type'] = $jobTypeAction->getWhereIn('id', $request->job_type_id ?? [1])->first();
        $data['job_types'] = $jobTypeAction->get();
        $data['job_titles'] = JobTitle::get();
        $data['nationalities'] = Nationality::get();
        $data['religions'] = Religion::get();
        $data['job_type_id'] = request()->job_type_id;

        // Add the request parameters to the pagination links
        if ($request->ajax()) {
            return view('web.ingaz.maids.maid_div', $data)->with('filters', $request->all());
        }
        // dd($request->all());

        $data['filters'] = $request->all();
        return view('web.ingaz.maids.index', $data);
    }
    public function show(MaidAction $action, $id, AdminAction $userAction, JobTypeAction $jobTypeAction)
    {
        $type = request('order_type');
        $data['maid'] = $action->find($id);
        $data['type'] = $jobTypeAction->find($type);

        //settings

        if ($type == 1) {
            $data['customer_services'] = $userAction->getWhere(['admin_type' => AdminTypes::CustomerService->value])->sortBy('sort', SORT_ASC);
            // dd($data);

            return view('web.ingaz.maids.show', $data);
        }
        if ($type == 2 || $type == 3) {

            // dd(2);
            $data['packages'] = Package::where(['nationality_id' => $data['maid']->nationality_id])->get();
            $data['addresses'] = UserAddress::where(['user_id' => getGuardedUser('id')])->get();

            return view('web.ingaz.maids.show', $data);
        }
    }
}
