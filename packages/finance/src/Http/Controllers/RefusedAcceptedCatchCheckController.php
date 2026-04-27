<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\RefusedAcceptedCheckService as ObjService;
use Yajra\DataTables\DataTables;

class RefusedAcceptedCatchCheckController extends Controller
{
    public string $mainRoute = "finance.catch-checks-data";


    public function index(Request $request, ObjService $service)
    {
        $dataTable = $service->getRefusedAcceptedCheckDataTable($request->bond_type,$request->status);
        if ($request->ajax()) {
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('debit_account_id', function ($row) {
                    return @$row->bond->creditAccount->name;
                })
                ->editColumn('credit_account_id', function ($row) {
                    return @$row->bond->debitAccount->name;
                })
                ->addColumn('actions', function ($row) use ($request) {
                    $showBtn = '<button type="button" class="btn btn-dark editButton" data-bs-toggle="modal" data-bs-target="#createModal" model-route="' . route('finance.bonds-sarf.show', $row->bond->id) . '" model-title="تفاصيل سند الصرف"><i class="fa fa-eye"></i></button>';
                    return $showBtn;
                })
                ->escapeColumns([])
                ->make(true);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $folderPath = "finance::finance.{$request->status}-catch-checks";
        return view($folderPath . '.index', $data);
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
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
