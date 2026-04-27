<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\Bond\StoreBondRequest as StoreRequest;
use Nami\Finance\Http\Requests\Bond\UpdateBondRequest as UpdateRequest;
use Nami\Finance\Services\BondService as ObjService;
use Nami\Finance\Services\YearService;

class BondPrintController extends Controller
{
    public $folderPath = "finance::finance.bond.sarf";
    public $mainRoute = "finance.bonds-print";

    public function __construct()
    {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, ObjService $service, YearService $yearService)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, ObjService $service , $id)
    {
        $data['bond'] = $service->find($id)->loadMissing(['creditAccount', 'debitAccount', 'createdBy', 'check', 'bondTax']);
        if($request->type == 'qabd'){
            return view('finance::finance.bond.qabd.print_qabd', $data);
        }
        return view($this->folderPath . '.print_sarf', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, $id, ObjService $service, YearService $yearService)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {

    }
}
