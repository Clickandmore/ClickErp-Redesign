<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\ClientAction;
use App\Http\Actions\ingaz\SelectWorkerAction;
use App\Http\Actions\ingaz\SelectWorkerSettingsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiSelectWorkerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        SelectWorkerAction $action,
        ClientAction $clientAction,
        SelectWorkerSettingsAction $settingsAction
    ) {
        $selectWorkers = $action->get();
        $clients = $clientAction->get();
        $selectedSettings = $settingsAction->first();

        return apiResponse(true, __('api.All items'), [
            'selectWorkers' => $selectWorkers,
            'clients' => $clients,
            'selectedSettings' => $selectedSettings,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }
}
