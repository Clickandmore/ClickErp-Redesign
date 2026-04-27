<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Services\VacationYearService as ObjService;

class ChangeYearStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function update(Request $request, int $id, ObjService $service)
    {
        $status = $request->status;
        if ($status == 1) {
            $service->deactivateAll();
        }
        $data = $service->find($id);
        $dataInsert['status'] = !$data->status;
        $data->update($dataInsert);
        return jsonSuccess();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
