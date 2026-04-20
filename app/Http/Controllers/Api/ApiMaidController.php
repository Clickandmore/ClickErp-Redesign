<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use Illuminate\Http\Request;
use App\Http\Actions\ingaz\ArrivalWorkerAction;
use App\Http\Actions\ingaz\ClientAction;

class ApiMaidController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(
        Request $request,
        MaidAction $maidAction,
        JobTitleAction $jobTitleAction,
        NationalityAction $nationalityAction
    ) {
        $data['maids'] = $maidAction->paginate($request);

        $data['type'] = $request->type ?? 'all';
        $data['jobTitles'] = $jobTitleAction->get();
        $data['nationalities'] = $nationalityAction->get();
        $data['blade_title'] = match ($request->type) {
            'all' => __('ingaz.recruitment_request'),
            'estqdam' => __('ingaz.recruitment_request'),
            'egar' => __('ingaz.labor_rental_request'),
            'transfer' => __('ingaz.Labor_transfer_request'),
            'has_experience_label' => __('ingaz.has_experience_label'),
            'no_experience_label' => __('ingaz.no_experience_label'),
            default => __('ingaz.recruitment_request')
        };

        return apiResponse(true, __('api.All items'), $data, 200);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request, MaidAction $maidAction)
    {
        $type = $request->query('type') ?? null;

        $maid = Maid::with(['jobTitles', 'religion', 'nationality', 'recruitmentOffice' => function ($q) {
            $q->withCount('maids');
        }])->find($id);

        $customerServices = Admin::query()
            ->whereNull('deleted_at')
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'active');
            })
            ->orderBy('name')
            ->get();

        if (!$maid) {
            return apiResponse(false, 'العاملة غير موجودة', null, 404);
        }

        $data = [
            'maid' => $maid,
            'type' => $type,
            'customerServices' => $customerServices,
        ];

        return apiResponse(true, 'تم جلب البيانات بنجاح.', $data, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function arrivalWorker(ClientAction $clientAction, ArrivalWorkerAction $arrivalWorkerAction)
    {
        $clients = $clientAction->get();
        $arrivalWorkers = $arrivalWorkerAction->get();

        $data = [
            'clients' => $clients,
            'arrivalWorkers' => $arrivalWorkers,
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }
}
