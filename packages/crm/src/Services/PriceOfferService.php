<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\Client;
use Nami\Crm\Models\PriceOffer;
use Nami\Crm\Models\PriceOfferDetail;
use App\Services\MainService;


class PriceOfferService extends MainService
{

    function __construct()
    {
        $this->model = new PriceOffer();
        $this->fileFolder = "images/admins/offers/";
    }

    public function getClients() {
        return Client::where('status', 1)->get();
    }


    public function storePriceOffer($data,$request) {
        $data['desc'] = $request->desc_offer;
        if (isset($request->file)) {
            $image = $request->file;
            $data['file'] = uploadFile($image, $this->fileFolder);
        }
        $price_offer = $this->store($data);
        $total = $request->total;
//        $total = $this->storeDetails($request, $price_offer, $total);
//        $price_offer->total = $total;
        $price_offer->save();
        return $price_offer;
    }

    public function storeDetails($request, $price_offer, $total) {
        if ($request->service_id){
            foreach ($request->service_id as $key => $service_id) {
                $service = $request->service_id[$key];
                $amount = $request->amount[$key];
                $price = $request->price[$key];
                $total += $amount * $price;
                PriceOfferDetail::create([
                    "price_offer_id" => $price_offer->id,
                    "service_id" => $service_id,
                    "amount" => 1,
                    "discount" => $request->discount[$key],
                    "description" => $request->description[$key],
                    "price" => $price,
                ]);
            }
        }
        return $total;
    }

    public function updatePriceOffer($id,$data,$request) {
        $obj = $this->find($id);
        $data['desc'] = $request->desc_offer;
        if (isset($request->file)) {
            $image = $request->file;
            $data['file'] = uploadFile($image, $this->fileFolder);
        }
        $price_offer = $obj->update($data);
        $total = $request->total;
        PriceOfferDetail::where('price_offer_id', $id)->delete();
        $total = $this->storeDetails($request, $obj, $total);
        $obj->total = $total;
        $obj->save();
        return $price_offer;
    }

    public function getCustomers()
    {
        return Client::get();
    }
}
