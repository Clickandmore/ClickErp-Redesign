<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\ClientService as ObjService;
use Nami\Crm\Http\Requests\ClientRequest as StoreRequest;
use Nami\Crm\Models\Service;
use Illuminate\Http\Request;
use Nami\Crm\Models\Client as ObjModel;
use Yajra\DataTables\DataTables;


class ClientReportController extends Controller
{
    public $folderPath = "crm::crm.clients.reports";
    public $mainRoute = "crm.clients_report";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return
                            '<a type="button" class=" editButton" data-toggle="modal" data-target="#createModal"
                            model-route="' . route($this->mainRoute . ".show", $row->id) . '" model-title="' . $row->name . '">
                        '.$row->name.'
                    </a>';
                })
                ->addColumn('price_offer_count', function ($row) {
                    return @$row->priceOffers()->count();
                })
                ->addColumn('service_id', function ($row) {
                    return @$row->priceOffersDetails()->count();
                })
                ->editColumn('status', function ($row) {
                    if ($row->status == 1) {
                        $status = "<button class='btn btn-success update-status' data-id='" . $row->id . "'>" . __("finance.show") . "</button>";
                    } else {
                        $status = "<button class='btn btn-danger update-status' data-id='" . $row->id . "'>" . __("finance.hidden") . "</button>";
                    }
                    return $status;
                })
                ->filter(function ($instance) use ($request) {
                    if ($request->get('social_contact_id')) {
                        $instance->where('social_contact_id', $request->get('social_contact_id'));
                    }
                    if ($request->get('category_id')) {
//                        dd($request->get('category_id'));
                        $instance->whereHas('category',function ($q) use($request){
                             $q->where('category_id',$request->get('category_id'));
                        });
                    }
                    if ($request->get('city_id')) {
                        $instance->where('city_id', $request->get('city_id'));
                    }
                    if ($request->get('area_id')) {
                        $instance->where('area_id', $request->get('area_id'));
                    }

                    if ($request->get('from_date')) {
                        $instance->whereDate('created_at','<=', $request->get('from_date'));
                    }
                    if ($request->get('to_date')) {
                        $instance->where('created_at','>=', $request->get('to_date'));
                    }
                    if ($request->get('service_id')) {
                        $serviceId = $request->get('service_id');
                        $instance->whereHas('priceOffersDetails', function($query) use ($serviceId) {
                            $query->where('service_id', $serviceId);
                        });
                    }
                })
               ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute );
        $data["dataTableRoute"] = route($this->mainRoute);
        $data["bladeTitle"] = __("crm.clients_report");
        $data["addButtonText"] = __("crm.clients_reportreport");
        // $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }


    public function show(Request $request,$id, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
                "cities" => $service->getCities(),
                "areas" => $service->getAreas(),
                "social_contacts" => $service->getSocialContacts(),
                "users" => $service->getUsers(),
                "categories" => $service->getCategories(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function withRegions(Request $request, ObjService $service)
    {
        $data["cities"] = $service->getCities();
        $data["areas"] = $service->getAreas();
        $data["createRoute"] = route($this->mainRoute);
        $data["dataTableRoute"] = route($this->mainRoute);
        $data["bladeTitle"] = __("crm.clients_report_with_regions");
        $data["addButtonText"] = __("crm.clients_report_with_regions");
        // $data["modalType"] = "";
        return view($this->folderPath . '.with_regions', $data);
    }


    public function withCategory(Request $request, ObjService $service)
    {
        $data["social_contacts"] = $service->getSocialContacts();
        $data["categories"] = $service->getCategories();
        $data["createRoute"] = route($this->mainRoute);
        $data["dataTableRoute"] = route($this->mainRoute);
        $data["bladeTitle"] = __("crm.clients_report_with_category");
        $data["addButtonText"] = __("crm.clients_report_with_category");
        // $data["modalType"] = "";
        return view($this->folderPath . '.with_category', $data);
    }
}
