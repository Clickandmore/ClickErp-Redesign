<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Http\Actions\ingaz\ArrivalWorkerAction;
use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\PackageResource;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Package;
use App\Models\ingaz\Skill;
use Illuminate\Http\Request;

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

        $maid = Maid::with(['jobTitles', 'religion', 'nationality', 'expertises.country', 'expertises.jobTitle', 'recruitmentOffice' => function ($q) {
            $q->withCount('maids');
        }])->find($id);

        $skills = Skill::all();

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

        $packages = Package::with('translations')
            ->visible()
            ->forNationality($maid->nationality_id)
            ->orderBy('days_count')
            ->orderBy('price_with_tax')
            ->get();

        $data = [
            'maid' => $maid,
            'packages' => PackageResource::collection($packages),
            'experience' => [
                'status' => $maid->experience_status,
                'countries' => $maid->expertises->map(function ($expertise) {
                    return [
                        'country_id' => $expertise->country_id,
                        'country_name_ar' => $expertise->country?->name_ar,
                        'country_name_en' => $expertise->country?->name_en,
                        'number_of_years' => $expertise->number_of_years,
                        'job_title_id' => $expertise->job_title_id,
                        'job_title' => $expertise->jobTitle?->title,
                    ];
                })->values(),
            ],
            'type' => $type,
            'customerServices' => $customerServices,
            'skills' => $skills,
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
