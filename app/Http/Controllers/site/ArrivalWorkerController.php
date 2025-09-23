<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\ArrivalWorkerAction;
use App\Http\Actions\ingaz\ClientAction;
use App\Http\Controllers\Controller;

class ArrivalWorkerController extends Controller
{
    public function index(ClientAction $clientAction, ArrivalWorkerAction $arrivalWorkerAction)
    {
        $clients = $clientAction->get();
        $arrivalWorkers = $arrivalWorkerAction->get();
        return view('site.pages.arrival_workers', get_defined_vars());
    }


}
