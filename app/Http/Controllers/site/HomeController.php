<?php

namespace App\Http\Controllers\site;

use App\Enums\Office\OfficesStatusEnum;
use App\Http\Actions\ingaz\ServiceAction;
use App\Http\Actions\ingaz\SliderAction;
use App\Http\Actions\ingaz\StatisticAction;
use App\Http\Actions\ingaz\StepAction;
use App\Http\Actions\office\OfficeAction as objAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, SliderAction $sliderAction,
                          StatisticAction $statisticAction , ServiceAction $serviceAction , StepAction $stepAction)
    {
        $sliders = $sliderAction->get();
        $statics = $statisticAction->get();
        $services = $serviceAction->get();
        $steps = $stepAction->get();
        return view('site.pages.home', get_defined_vars());
    }

}
