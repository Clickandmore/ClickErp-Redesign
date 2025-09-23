<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\SelectWorkerAction;
use App\Http\Controllers\Controller;

class SelectWorkerController extends Controller
{
    public function index(SelectWorkerAction $action , ClientAction $clientAction)
    {
        $selectWorkers = $action->get();
        $clients = $clientAction->get();
        return view('site.pages.select_worker', get_defined_vars());
    }


}
