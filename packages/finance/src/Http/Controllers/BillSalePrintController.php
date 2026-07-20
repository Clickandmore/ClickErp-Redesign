<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\BillSalesService as ObjService;
use Nami\Finance\Services\YearService;
use Nami\Finance\Services\ZatcaSettingService;

class BillSalePrintController extends Controller
{
    public $folderPath = "finance::finance.bill_sales";
    public $mainRoute = "finance.bill-sales-print";

    public function __construct()
    {
        $this->middleware('checkActiveYear')->only(['create','edit']);
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
    public function store(Request $request, ObjService $service, YearService $yearService)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(
        Request $request,
        ObjService $service,
        ZatcaSettingService $zatcaSettingService,
        $id
    )
    {
        $data = $service->getInvoiceDocumentData((int) $id);
        $data['zatcaSetting'] = $zatcaSettingService->getSetting();

        return view($this->folderPath . '.print', $data);
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
    public function update(Request $request, $id, ObjService $service, YearService $yearService)
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
