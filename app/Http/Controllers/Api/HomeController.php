<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Enums\ingaz\NotificationActionEnum;
use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\Api\DailyLabotDemandAction;
use App\Http\Actions\ingaz\AboutUsAction;
use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\FeatureAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RequirementAction;
use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Actions\ingaz\TestimonialsAction;
use App\Http\Actions\ingaz\WhatDoAction;
use App\Http\Actions\ingaz\WhatDoSettingAction;
use App\Http\Actions\ingaz\WhyUsAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\DailyLabotDemandRequest;
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
use Illuminate\Support\Str;

class HomeController extends Controller
{
    use SendSMSTrait, ApiResponse, StoreMessageTrait;

    public function contractStatus(Request $request)
    {
        $contractQuery = Contract::query()->with(['user', 'maid', 'info']);
        $hasFilter = false;
        if (!empty($request->inpuut_id)) {
            $hasFilter = true;
            $contractQuery->whereHas('user', function ($q) use ($request) {
                $q->where('national_num', $request->inpuut_id);
            });
        } elseif (!empty($request->inpuut_musand_id)) {
            $hasFilter = true;
            $contractQuery->where(function ($q) use ($request) {
                $q->where('msaned_number', $request->inpuut_musand_id)
                    ->orWhereHas('info', function ($infoQuery) use ($request) {
                        $infoQuery->where('msaned_number', $request->inpuut_musand_id);
                    });
            });
        } elseif (!empty($request->inpuut_passport_id)) {
            $hasFilter = true;
            $contractQuery->whereHas('maid', function ($q) use ($request) {
                $q->where('passport_num', $request->inpuut_passport_id);
            });
        } elseif (!empty($request->inpuut_visa_id)) {
            $hasFilter = true;
            $contractQuery->where(function ($q) use ($request) {
                $q->where('visa_number', $request->inpuut_visa_id)
                    ->orWhereHas('info', function ($infoQuery) use ($request) {
                        $infoQuery->where('visa_number', $request->inpuut_visa_id);
                    });
            });
        }

        $contract = $hasFilter ? $contractQuery->latest()->first() : null;

        if (!empty($contract)) {
            $status = getEnumValue('contract_statuses', $contract->status);
            if ($status === 'undefined') {
                $status = $contract->status;
            }

            return [
                "status" => 1,
                "data" => $status
            ];
        } else {
            return [
                "status" => 0,
            ];
        }
    }
    public function nationalites(Request $request)
    {
        $nationalities = Nationality::where('is_show', 1)->get()->pluck('id', 'title');

        return [
            "status" => 1,
            "data" => $nationalities
        ];
    }
    public function allStaff(Request $request)
    {
        $staffs = Admin::where('admin_type', AdminTypes::CustomerService->value)->get();
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
            $msg = " عزيزنا العميل ,تم تسليم الشكوى لدى مكتب بلاتنيوم الشرقية للاستقدام بنجاح . وهى محل اهتمامنا و سيتم المتابعة و أعلامك بالمستجدات رقم الشكوى" . $ticket->code;
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
    ) {

        $sliders = $sliderAction->get();
        $statics = $statisticAction->get();
        $services = $serviceAction->get();
        $steps = $stepAction->get();
        $clients = $clientAction->get();
        $whatDos = $whatDoAction->get();
        $whatDoSetting = $whatDoSettingAction->first();
        $about = $about->first();
        $features = $feature->get();
        $nationalities = method_exists($nationalityAction, 'getVisible')
            ? $nationalityAction->getVisible()
            : $nationalityAction->get();
        $requirments = $requirement_action->get();
        $Visa = $requirments->where('type', RequirementEnum::VisaIssuance->value);
        $Domestic = $requirments->where('type', RequirementEnum::RecruitDomestic->value);

        $counts['number_recruitment_requests'] = DB::table('orders')->get()->count();
        $counts['number_contracts_requests'] = DB::table('contracts')->get()->count();

        $counts['clients'] = DB::table('users')->count();

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
            'nationalities' => $nationalities,
            'requirements' => [
                'visa' => $Visa,
                'domestic' => $Domestic,
            ],
            'counts' => $counts,
        ];

        return $this->successResponse($data, 'Home data retrieved successfully', 200);
    }


    public function whyUs(Request $request, WhyUsAction $whyUsAction)
    {
        $whyUs = $whyUsAction->get();
        return $this->successResponse($whyUs, 'Why Us data retrieved successfully', 200);
    }
    public function testimonials(Request $request, TestimonialsAction $testimonialsAction)
    {
        $testimonials = $testimonialsAction->get();
        return $this->successResponse($testimonials, 'Testimonials data retrieved successfully', 200);
    }

    public function storeTestimonial(DailyLabotDemandRequest $request, DailyLabotDemandAction $dailyLabotDemandAction)
    {
        $data = $request->validated();
        $result = $dailyLabotDemandAction->store($data);
        return jsonSuccess($result, __("ingaz.Daily_Labot_Demand_Request_Sent_Successfully"));
    }
}
