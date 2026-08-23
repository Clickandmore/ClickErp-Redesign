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
use App\Models\Branch;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Package;
use App\Models\ingaz\Skill;
use App\Support\SystemModuleManager;
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

        // ملاحظة "متاحة للإيجار ونقل الخدمات" تُحسب هنا فقط (العلاقات محمّلة مسبقًا)
        // بدلاً من إضافتها إلى $appends وتحميل استعلامات إضافية على كل شاشات الإدارة.
        $data['maids']->getCollection()->each->append('is_rental_transfer_listing');
        $this->attachRentalPackages($data['maids']->getCollection());
        $this->attachServiceCosts($data['maids']->getCollection());

        $data['type'] = $request->type ?? 'all';
        $data['jobTitles'] = $jobTitleAction->get();
        $data['nationalities'] = $nationalityAction->getVisible()
            ->map(function ($nationality) {
                $payload = $nationality->toArray();
                $payload['title'] = filled($nationality->title)
                    ? $nationality->title
                    : $nationality->sub_title;

                return $payload;
            })
            ->values();
        $data['branches_enabled'] = SystemModuleManager::isEnabled('branches');
        $data['branches'] = $data['branches_enabled']
            ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en'])
                ->map(fn (Branch $branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                ])->values()
            : collect();
        $data['blade_title'] = match ($request->type) {
            'all' => __('ingaz.recruitment_request'),
            'estqdam' => __('ingaz.recruitment_request'),
            'egar' => __('ingaz.labor_rental_request'),
            'transfer' => __('ingaz.Labor_transfer_request'),
            'has_experience_label' => __('ingaz.has_experience_label'),
            'no_experience_label' => __('ingaz.no_experience_label'),
            default => __('ingaz.recruitment_request')
        };

        return apiResponse(true, __('api.All items'), $data, 200)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }


    /**
     * إرفاق باقات التأجير المتاحة لجنسية كل عاملة بحقل إضافي `rental_packages`،
     * دون المساس بأي حقل قائم حتى لا تتأثر النسخ الأخرى المرتبطة بهذا الـ API.
     * تُحمَّل الباقات باستعلام واحد لكل الصفحة تجنبًا لمشكلة N+1.
     */
    private function attachRentalPackages($maids): void
    {
        $nationalityIds = collect($maids)
            ->pluck('nationality_id')
            ->filter()
            ->unique()
            ->values();

        $packages = Package::query()
            ->visible()
            ->ordered()
            ->where(function ($query) use ($nationalityIds) {
                $query->whereNull('nationality_id');

                if ($nationalityIds->isNotEmpty()) {
                    $query->orWhereIn('nationality_id', $nationalityIds->all());
                }
            })
            ->get();

        $sharedPackages = $packages->whereNull('nationality_id')->values();
        $packagesByNationality = $packages->whereNotNull('nationality_id')->groupBy('nationality_id');
        $request = request();

        foreach ($maids as $maid) {
            $maidPackages = collect($packagesByNationality->get($maid->nationality_id, collect()))
                ->concat($sharedPackages)
                ->values();

            $maid->setAttribute(
                'rental_packages',
                $maidPackages
                    ->map(fn (Package $package) => (new PackageResource($package))->toArray($request))
                    ->all()
            );
        }
    }

    /**
     * إرفاق تكلفة كل خدمة بالسيرة حتى يعرض الموقع الخارجي قيمة نقل الخدمات
     * القادمة من الجنسية بدل قيمة الاستقدام أو الراتب على بطاقة نقل الخدمات.
     * الجنسية محمّلة مسبقًا في الاستعلام فلا استعلامات إضافية هنا.
     */
    private function attachServiceCosts($maids): void
    {
        foreach ($maids as $maid) {
            foreach ($maid->serviceCostsForSite() as $key => $value) {
                $maid->setAttribute($key, $value);
            }
        }
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
            ->ordered()
            ->get();

        $recruitmentOffice = $maid->recruitmentOffice;
        $recruitmentOfficePayload = $recruitmentOffice ? [
            'id' => $recruitmentOffice->id,
            'name' => $recruitmentOffice->title,
            'title' => $recruitmentOffice->title,
            'address' => $recruitmentOffice->street,
            'street' => $recruitmentOffice->street,
            'phone' => $recruitmentOffice->phone_office,
            'phone_office' => $recruitmentOffice->phone_office,
            'manager_phone' => $recruitmentOffice->manager_phone,
            'email' => $recruitmentOffice->manager_email,
            'manager_email' => $recruitmentOffice->manager_email,
        ] : null;

        $maidPayload = array_merge($maid->toArray(), $maid->serviceCostsForSite());
        $maidPayload['recruitment_office'] = $recruitmentOfficePayload;
        $maidPayload['is_rental_transfer_listing'] = $maid->is_rental_transfer_listing;

        $data = [
            'maid' => $maidPayload,
            'packages' => PackageResource::collection($packages),
            'experience' => [
                'status' => $maid->experience_status,
                'countries' => $maid->expertises->map(function ($expertise) {
                    return [
                        'country_id' => $expertise->country_id,
                        'country_name_ar' => $expertise->country?->name_ar,
                        'country_name_en' => $expertise->country?->name_en,
                        'work_city' => $expertise->work_city,
                        'start_date' => $expertise->start_date?->toDateString(),
                        'end_date' => $expertise->end_date?->toDateString(),
                        'number_of_years' => $expertise->number_of_years,
                        // جاهزة للعرض: "2018م – 2022م" و "4 سنوات و 5 أشهر"
                        'duration_months' => $expertise->duration_months,
                        'period_label' => $expertise->period_label,
                        'duration_label' => $expertise->duration_label,
                        'job_title_id' => $expertise->job_title_id,
                        'job_title' => $expertise->jobTitle?->title,
                    ];
                })->values(),
            ],
            'type' => $type,
            'customerServices' => $customerServices,
            'skills' => $skills,
        ];

        return apiResponse(true, 'تم جلب البيانات بنجاح.', $data, 200)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
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
