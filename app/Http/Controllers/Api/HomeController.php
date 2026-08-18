<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\Api\DailyLabotDemandAction;
use App\Http\Actions\ingaz\AboutUsAction;
use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\FeatureAction;
use App\Http\Actions\ingaz\FindUsLocationAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\PackageAction;
use App\Http\Actions\ingaz\QuestionAction;
use App\Http\Actions\ingaz\RequirementAction;
use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Actions\ingaz\TestimonialsAction;
use App\Http\Actions\ingaz\WhatDoAction;
use App\Http\Actions\ingaz\WhatDoSettingAction;
use App\Http\Actions\ingaz\WhyUsAction;
use App\Http\Actions\ingaz\WhyChooseUsAction;
use App\Http\Actions\ingaz\MusanedSectionAction;
use App\Http\Actions\ingaz\HomeHeroSectionAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\DailyLabotDemandRequest;
use App\Http\Resources\Api\PackageResource;
use App\Http\Resources\Api\FindUsLocationResource;
use App\Http\Resources\Api\RecruitmentRequirementResource;
use App\Http\Resources\Api\WhyChooseUsResource;
use App\Http\Resources\Api\MusanedSectionResource;
use App\Http\Resources\Api\HomeHeroSectionResource;
use App\Http\Traits\API\ApiResponse;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    use SendSMSTrait, ApiResponse, StoreMessageTrait;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $this->setApiLocale($request);

            return $next($request);
        });
    }

    private function setApiLocale(Request $request): void
    {
        $locale = strtolower((string) ($request->header('lang') ?: $request->header('Accept-Language', 'ar')));
        $locale = substr($locale, 0, 2);

        if (!in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);
    }

    private function localizedText(string $ar, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $ar;
    }

    private function buildContractStatusMessage(Contract $contract, string $status): string
    {
        $lines = [
            $this->localizedText('رقم العقد: ', 'Contract Number: ') . $contract->id,
            $this->localizedText('حالة العقد: ', 'Contract Status: ') . $status,
        ];

        if ($contract->info?->msaned_number) {
            $lines[] = $this->localizedText('رقم عقد مساند: ', 'Msaned Contract Number: ') . $contract->info->msaned_number;
        }

        if ($contract->info?->visa_number) {
            $lines[] = $this->localizedText('رقم التأشيرة: ', 'Visa Number: ') . $contract->info->visa_number;
        }

        if ($contract->maid?->passport_num) {
            $lines[] = $this->localizedText('رقم جواز العاملة: ', 'Worker Passport Number: ') . $contract->maid->passport_num;
        } elseif ($contract->info?->manual_maid_passport_num) {
            $lines[] = $this->localizedText('رقم جواز العاملة: ', 'Worker Passport Number: ') . $contract->info->manual_maid_passport_num;
        }

        return implode("\n", $lines);
    }

    public function contractStatus(Request $request)
    {
        $searchValue = trim(strtr((string) $request->input('search_value', ''), [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ]));
        $searchValue = trim($searchValue, " \t\n\r\0\x0B#");

        $contractQuery = Contract::query()->with(['user', 'maid.nationalitiesy', 'info']);

        if ($searchValue !== '') {
            $contractQuery->where(function ($query) use ($searchValue) {
                if (ctype_digit($searchValue)) {
                    $query->orWhere('id', $searchValue);
                }

                if (Schema::hasColumn('contracts', 'msaned_number')) {
                    $query->orWhere('msaned_number', $searchValue);
                }

                if (Schema::hasColumn('contracts', 'visa_number')) {
                    $query->orWhere('visa_number', $searchValue);
                }

                $query->orWhereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where('national_num', $searchValue);
                });

                $query->orWhereHas('maid', function ($maidQuery) use ($searchValue) {
                    $maidQuery->where('passport_num', $searchValue);
                });

                $query->orWhereHas('info', function ($infoQuery) use ($searchValue) {
                    $infoQuery->where('msaned_number', $searchValue)
                        ->orWhere('visa_number', $searchValue);

                    if (Schema::hasColumn('contract_infos', 'manual_maid_passport_num')) {
                        $infoQuery->orWhere('manual_maid_passport_num', $searchValue);
                    }
                });
            });
        } elseif (!empty($request->inpuut_id)) {
            $contractQuery->whereHas('user', function ($q) use ($request) {
                $q->where('national_num', $request->inpuut_id);
            });
        } elseif (!empty($request->inpuut_musand_id)) {
            $contractQuery->where(function ($q) use ($request) {
                if (Schema::hasColumn('contracts', 'msaned_number')) {
                    $q->where('msaned_number', $request->inpuut_musand_id);
                }

                $q->orWhereHas('info', function ($infoQuery) use ($request) {
                    $infoQuery->where('msaned_number', $request->inpuut_musand_id);
                });
            });
        } elseif (!empty($request->inpuut_passport_id)) {
            $contractQuery->where(function ($query) use ($request) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->where('passport_num', $request->inpuut_passport_id);
                });

                if (Schema::hasColumn('contract_infos', 'manual_maid_passport_num')) {
                    $query->orWhereHas('info', function ($infoQuery) use ($request) {
                        $infoQuery->where('manual_maid_passport_num', $request->inpuut_passport_id);
                    });
                }
            });
        } elseif (!empty($request->inpuut_visa_id)) {
            $contractQuery->where(function ($q) use ($request) {
                if (Schema::hasColumn('contracts', 'visa_number')) {
                    $q->where('visa_number', $request->inpuut_visa_id);
                }

                $q->orWhereHas('info', function ($infoQuery) use ($request) {
                    $infoQuery->where('visa_number', $request->inpuut_visa_id);
                });
            });
        } else {
            return [
                "status" => 0,
            ];
        }

        $contract = $contractQuery->latest()->first();

        if (!empty($contract)) {
            $status = getEnumValue('contract_statuses', $contract->status);
            if ($status === 'undefined') {
                $status = $contract->status;
            }

            $message = "رقم العقد: {$contract->id}\n";
            $message .= "حالة العقد: {$status}";

            if ($contract->info?->msaned_number) {
                $message .= "\nرقم عقد مساند: {$contract->info->msaned_number}";
            }

            if ($contract->info?->visa_number) {
                $message .= "\nرقم التأشيرة: {$contract->info->visa_number}";
            }

            if ($contract->maid?->passport_num) {
                $message .= "\nرقم جواز العاملة: {$contract->maid->passport_num}";
            } elseif ($contract->info?->manual_maid_passport_num) {
                $message .= "\nرقم جواز العاملة: {$contract->info->manual_maid_passport_num}";
            }

            $message = $this->buildContractStatusMessage($contract, $status);

            return [
                "status" => 1,
                "data" => $message
            ];
        } else {
            return [
                "status" => 0,
            ];
        }
    }
    public function nationalites(Request $request)
    {
        $nationalities = Nationality::where('is_show', 1)->ordered()->get()->pluck('id', 'title');

        return [
            "status" => 1,
            "data" => $nationalities
        ];
    }
    public function allStaff(Request $request)
    {
        $staffs = Admin::query()
            ->activeForOperations()
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->get();
        $users = $staffs->mapWithKeys(function ($staff) {
            $phone = !empty($staff->whatsapp) ? $staff->whatsapp : $staff->phone;
            return [$staff->name => $phone];
        });
        return [
            "status" => 1,
            "data" => $users
        ];
    }
    public function storeTicket(Request $request)
    {
        $user = User::where('phone', 'like', '%' . $request->phone  . '%')->first();
        $ticket = new Complaint;
        if (!empty($user)) {
            $ticket->user_id = $request->user_id ?? $user->id;
        } else {
            $user = new User();
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->phone_code = $request->phone_code ?? '966';
            $user->password = bcrypt(Str::random(12));
            $user->register_from = 'api';
            $user->save();
            $ticket->user_id = $user->id;
        }

        $ticket->subject = $request->subject;
        $ticket->details = $request->message;
        $ticket->message = $request->message;
        $ticket->address = $request->link ?? $request->address ?? '';
        $ticket->complainant_name = $request->name ?? $user->name;
        $ticket->complainant_phone = $request->phone ?? $user->phone;

        if ($ticket->save()) {
            $ticket->code = $ticket->id;
            $companyName = documentSetting('document_company_name', documentSetting('website_name', config('app.name')));
            $msg = " عزيزنا العميل ,تم تسليم الشكوى لدى شركة {$companyName} بنجاح . وهى محل اهتمامنا و سيتم المتابعة و أعلامك بالمستجدات رقم الشكوى" . $ticket->code;
            if (!empty($user->phone)) {
                $result = $this->sendSMS(formatSaudiPhone($user->phone), $msg);

                if (isset($result['code']) && $result['code'] == "1") {
                    $this->storeMessage('ارسال رسالة نصية الى العميل', $msg, NotificationActionEnum::SMS->value, $user->id);
                }
            }
            return [
                "status" => 1,
            ];
        } else {
            return [
                "status" => 0,
            ];
        }
    }


    // 3mer
    public function homeSections(
        Request $request,
        SliderAction $sliderAction,
        WhatDoSettingAction $whatDoSettingAction,
        NationalityAction $nationalityAction,
        WhatDoAction $whatDoAction,
        StatisticAction $statisticAction,
        ServiceAction $serviceAction,
        StepAction $stepAction,
        ClientAction $clientAction,
        RequirementAction $requirement_action,
        AboutUsAction $about,
        FeatureAction $feature,
        PackageAction $packageAction,
        QuestionAction $questionAction,
        WhyUsAction $whyUsAction,
        WhyChooseUsAction $whyChooseUsAction,
        MusanedSectionAction $musanedSectionAction,
        HomeHeroSectionAction $homeHeroSectionAction,
        FindUsLocationAction $findUsLocationAction,
    ) {

        $sliders = $sliderAction->get();
        $statics = $statisticAction->get();
        $services = $serviceAction->get();
        $steps = $stepAction->get();
        $clients = $clientAction->get();
        $whatDos = $whatDoAction->get()->load('items');
        $whatDos->each->append('image_url');
        $whatDoSetting = $whatDoSettingAction->first();
        $about = $about->first();
        $features = $feature->get();
        $packages = $packageAction->get()
            ->filter(fn ($package) => $package->is_show === true || $package->is_show === null)
            ->sortBy(fn ($package) => sprintf('%010d-%020.2f', (int) $package->days_count, (float) $package->price_with_tax))
            ->values();
        $questions = $questionAction->get()->where('is_show', 1)->values();
        $nationalities = method_exists($nationalityAction, 'getVisible')
            ? $nationalityAction->getVisible()
            : $nationalityAction->get();
        $requirments = $requirement_action->getVisible();
        $Visa = $requirments->where('type', RequirementEnum::VisaIssuance->value);
        $Domestic = $requirments->where('type', RequirementEnum::RecruitDomestic->value);
        $whyUs = $whyUsAction->get();
        $whyChooseUs = $whyChooseUsAction->visibleSection();
        $musanedSection = $musanedSectionAction->visibleSection();
        $homeHeroSection = $homeHeroSectionAction->visibleSection();
        $findUsLocations = $findUsLocationAction->ordered();

        $counts['number_recruitment_requests'] = DB::table('orders')->get()->count();
        $counts['number_contracts_requests'] = DB::table('contracts')->get()->count();

        $counts['clients'] = DB::table('users')->count();

        $customerService = Admin::query()
            ->activeForOperations()
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->select('id', 'name', 'phone', 'whatsapp')
            ->get();

        $data = [
            'sliders' => $sliders,
            'statistics' => $statics,
            'services' => $services,
            'steps' => $steps,
            'clients' => $clients,
            'what_dos' => $whatDos,
            'what_do_setting' => $whatDoSetting,
            'about' => $about,
            'features' => $features,
            'packages' => PackageResource::collection($packages),
            'questions' => $questions,
            'nationalities' => $nationalities,
            'customerService' => $customerService,
            'recruitment_requirements' => RecruitmentRequirementResource::collection(
                $requirments->values()
            ),
            'why_us' => $whyUs,
            'why_choose_us' => $whyChooseUs
                ? new WhyChooseUsResource($whyChooseUs)
                : null,
            'musaned' => $musanedSection
                ? new MusanedSectionResource($musanedSection)
                : null,
            'hero' => $homeHeroSection
                ? new HomeHeroSectionResource($homeHeroSection)
                : null,
            'find_us' => FindUsLocationResource::collection($findUsLocations),
            'requirements' => [
                'visa' => RecruitmentRequirementResource::collection($Visa->values()),
                'domestic' => RecruitmentRequirementResource::collection($Domestic->values()),
            ],
            'counts' => $counts,
        ];

        return $this->successResponse($data, $this->localizedText('تم جلب بيانات الصفحة الرئيسية بنجاح', 'Home data retrieved successfully'), 200);
    }

    public function recruitmentRequirements(
        Request $request,
        RequirementAction $requirementAction
    ) {
        return $this->successResponse(
            RecruitmentRequirementResource::collection(
                $requirementAction->getVisible()
            ),
            $this->localizedText(
                'تم جلب متطلبات الاستقدام بنجاح',
                'Recruitment requirements retrieved successfully'
            )
        );
    }

    public function about(Request $request, AboutUsAction $about)
    {
        $about = $about->first();

        return $this->successResponse($about, $this->localizedText('تم جلب بيانات من نحن بنجاح', 'About data retrieved successfully'), 200);
    }


    public function whyUs(Request $request, WhyUsAction $whyUsAction)
    {
        $whyUs = $whyUsAction->get();
        return $this->successResponse($whyUs, $this->localizedText('تم جلب بيانات لماذا نحن بنجاح', 'Why Us data retrieved successfully'), 200);
    }
    public function testimonials(Request $request, TestimonialsAction $testimonialsAction)
    {
        $testimonials = $testimonialsAction->get();
        return $this->successResponse($testimonials, $this->localizedText('تم جلب آراء العملاء بنجاح', 'Testimonials data retrieved successfully'), 200);
    }

    public function storeTestimonial(DailyLabotDemandRequest $request, DailyLabotDemandAction $dailyLabotDemandAction)
    {
        $data = $request->validated();
        $result = $dailyLabotDemandAction->store($data);
        return jsonSuccess($result, __("ingaz.Daily_Labot_Demand_Request_Sent_Successfully"));
    }
}
