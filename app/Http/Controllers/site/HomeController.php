<?php

namespace App\Http\Controllers\site;

use App\Enums\ingaz\RequirementEnum;
use App\Enums\Office\OfficesStatusEnum;
use App\Http\Actions\ingaz\AboutUsAction;
use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\FastNewAction;
use App\Http\Actions\ingaz\FeatureAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RequirementAction;
use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Actions\ingaz\WhatDoAction;
use App\Http\Actions\ingaz\WhatDoSettingAction;
use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request         $request, SliderAction $sliderAction, WhatDoSettingAction $whatDoSettingAction, NationalityAction $nationalityAction, WhatDoAction $whatDoAction,
                          StatisticAction $statisticAction, ServiceAction $serviceAction, StepAction $stepAction,
                          ClientAction    $clientAction, RequirementAction $requirement_action, AboutUsAction $about, FeatureAction $feature,
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
        $requirments = $requirement_action->get();
        $Visa = $requirments->where('type', RequirementEnum::VisaIssuance->value);
        $Domestic = $requirments->where('type', RequirementEnum::RecruitDomestic->value);

        return view('site.pages.home', get_defined_vars());
    }

}
