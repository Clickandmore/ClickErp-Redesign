<?php

namespace App\Http\Controllers\site;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Actions\ingaz\AboutUsAction;
use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\FeatureAction;
use App\Http\Actions\ingaz\FindUsLocationAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RequirementAction;
use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Actions\ingaz\WhatDoAction;
use App\Http\Actions\ingaz\WhatDoSettingAction;
use App\Http\Actions\ingaz\WhyChooseUsAction;
use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request         $request, SliderAction $sliderAction, WhatDoSettingAction $whatDoSettingAction, NationalityAction $nationalityAction, WhatDoAction $whatDoAction,
                          StatisticAction $statisticAction, ServiceAction $serviceAction, StepAction $stepAction,
                          ClientAction    $clientAction, RequirementAction $requirement_action, AboutUsAction $about, FeatureAction $feature,
                          WhyChooseUsAction $whyChooseUsAction,
                          FindUsLocationAction $findUsLocationAction,
    )
    {
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
        $requirments = $requirement_action->getVisible();
        $Visa = $requirments->where('type', RequirementEnum::VisaIssuance->value);
        $Domestic = $requirments->where('type', RequirementEnum::RecruitDomestic->value);
        $whyChooseUs = $whyChooseUsAction->visibleSection();
        $findUsLocations = $findUsLocationAction->ordered();

        // $counts['number_recruitment_requests'] = DB::table('recruitment_requests')->where('status', OrderEnum::Contracted->value)->count();
        // $counts['number_contracts_requests'] = DB::table('istiqdam_contracts')->where('status', ContractStatusEnum::ClientContracted->value)->count();

        $counts['number_recruitment_requests'] = DB::table('orders')->get()->count();
        $counts['number_contracts_requests'] = DB::table('contracts')->get()->count();

        $counts['clients'] = DB::table('users')->count();

        return view('site.pages.home', get_defined_vars());
    }

}
