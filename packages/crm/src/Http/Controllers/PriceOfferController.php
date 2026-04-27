<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Enums\PriceOfferStatus;
use Nami\Crm\Services\PriceOfferService as ObjService;
use Nami\Crm\Http\Requests\PriceOfferRequest;
use Illuminate\Http\Request;
use Nami\Crm\Models\PriceOffer as ObjModel;
use Yajra\DataTables\DataTables;


class PriceOfferController extends Controller
{
    public $folderPath = "crm::crm.price_offers";
    public $postData = ['client_id', 'date', 'total', 'status', 'terms', 'notes', 'desc', 'file', 'user_id'];
    public $mainRoute = "crm.price-offers";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('client_id', function ($row) {
                    return $row->client_id ? @$row->client->name : @$row->user->name;
                })
                ->filter(function ($instance) use ($request) {

                    if ($request->get('status')) {
                        $instance->where('status', $request->get('status'));
                    }
                    if ($request->get('client_id')) {
                        $instance->where('client_id', $request->get('client_id'));
                    }

                    if ($request->get('from_date')) {
                        $instance->whereDate('date', '<=', $request->get('from_date'));
                    }
                    if ($request->get('to_date')) {
                        $instance->where('date', '>=', $request->get('to_date'));
                    }
                })
                ->editColumn('status', function ($row) {
                    return $row->status->lang();
                })
                ->addColumn('actions', function ($row) {

                    $button =
                        '
                                <a href="https://wa.me/' . @$row->client->phone . '?text=Check%20this%20out%3A%20' . route("crm.print-price-offers.show", $row->id) . '"
   class="" target="_blank">
    <i class="fa-brands fa-whatsapp"></i>
</a>';

                    $printButton = '';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    if ($row->file) {
                        $printButton = viewButton(route($this->mainRoute . ".print", $row->id), $row->name, 'file', '', '', '', 'طباعة ملف عرض السعر');
                    }

                    return $printButton . " " . $button . " " . $editButton . " " . $deleteButton;

                })->rawColumns(['actions', 'client_id'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm::crm.price-offers");
        $data["addButtonText"] = __("crm::crm.price-offers");
        // $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'clients' => $service->getClients(),
                'customers' => $service->getCustomers(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(PriceOfferRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->storePriceOffer($dataInsert, $request);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),

            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function copy($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".copy")->with([
                "obj" => $service->find($id),
                'clients' => $service->getClients(),
                'customers' => $service->getCustomers(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                'clients' => $service->getClients(),
                "statuses" => PriceOfferStatus::cases(),
                'customers' => $service->getCustomers(),
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
    public function update(PriceOfferRequest $request, $id, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
        $data = $service->updatePriceOffer($id, $dataInsert, $request);
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
        $service->delete($id);
        return jsonSuccess();
    }


    public function report(Request $request, ObjService $service)
    {
        $data["clients"] = $service->getClients();
        $data["statuses"] = PriceOfferStatus::cases();
        $data["createRoute"] = route($this->mainRoute . ".index");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm.price-offers");
        $data["addButtonText"] = __("crm.price-offers");
        // $data["modalType"] = "";
        return view($this->folderPath . '.report', $data);
    }

    public function print($id, ObjService $service)
    {
        $offer = $service->find($id);
        return view($this->folderPath . '.print', compact('offer'));
    }

    public function printFile($id, Request $request, ObjService $service)
    {
        $obj = $service->find($id);
        return view($this->folderPath . ".printFile")->with([
            'bladeTitle' => __("crm.price-offers"),
            "obj" => $obj,
        ]);
    }

}
