<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\BillSalesService;
use Nami\Finance\Services\SaleAttachmentService as ObjService;
use Nami\Finance\Services\YearService;

class BillSaleAttachmentController extends Controller
{
    public $folderPath = "finance::finance.bill_sales";
    public $mainRoute = "finance.bill-sales-attachments";

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
    public function show(Request $request, ObjService $service , $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, BillSalesService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".images")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, ObjService $service)
    {
        $dataInsert = $request->all();
        $data = $service->storeAttachments($id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
        $service->deleteWithFile($id,'file');
        return jsonSuccess();
    }
}
