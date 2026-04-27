<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\PriceOfferService as ObjService;
use Nami\Crm\Http\Requests\PriceOfferRequest;
use Illuminate\Http\Request;
use Nami\Crm\Models\PriceOffer as ObjModel;
use Yajra\DataTables\DataTables;


class PriceOfferPrintController extends Controller
{
    public $folderPath = "crm::crm.price_offers";
    public $mainRoute = "crm.price-offers";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */


    public function show($id, ObjService $service)
    {
        $offer = $service->find($id);
        return view($this->folderPath . '.print', compact('offer'));
    }


}
