<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Services\ItemService as ObjService;

class StockItemController extends Controller
{
    public string $folderPath = "finance::finance.filter_items";
    public string $mainRoute = "finance.stock_items";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $data['stock_setting'] = $service->getStocksSetting();

            $data['items'] = $service->filter($request->all());
            $html = view($this->folderPath . '.by_stock', $data)->render();
            return jsonSuccess(["html" => $html]);
        }
    }


}
