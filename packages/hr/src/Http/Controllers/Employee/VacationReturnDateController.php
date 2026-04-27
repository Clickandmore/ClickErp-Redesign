<?php

namespace Nami\Hr\Http\Controllers\Employee;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\CheckReturnDateRequest;
use Nami\Hr\Services\EmployeeVacationService as objAction;

class VacationReturnDateController extends Controller
{
    public $folderPath = "hr.employees.vacations";
    public $mainRoute = "employee.vacations-return-date";
    public $fileFolder = "images/admins/employee_vacation/";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjAction $action)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, objAction $action)
    {
       //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit_return_date")->with([
                "obj" => $action->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CheckReturnDateRequest $request, $id, ObjAction $action)
    {
        $dataInsert = $request->validated();

         $action->update($id, $dataInsert);
        return jsonSuccess();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
