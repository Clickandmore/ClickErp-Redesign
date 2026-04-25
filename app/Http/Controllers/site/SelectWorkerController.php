<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\SelectWorkerAction;
use App\Http\Actions\ingaz\SelectWorkerSettingsAction;
use App\Http\Controllers\admin\Controller;

class SelectWorkerController extends Controller
{
    public function index(SelectWorkerAction $action , ClientAction $clientAction , SelectWorkerSettingsAction $settingsAction)
    {
        $selectWorkers = $action->get();
        $clients = $clientAction->get();
        $selectedSettings = $settingsAction->first();
        return view('site.pages.select_worker', get_defined_vars());
    }


}
