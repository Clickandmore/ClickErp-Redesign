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

class FilterItemController extends Controller
{
    public string $folderPath = "finance::finance.filter_items";
    public string $mainRoute = "finance.filter-items";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {

            $data['items'] = $service->filter($request->all());
            $html = view($this->folderPath . '.by_supplier', $data)->render();
            return jsonSuccess(["html" => $html]);
        }
    }


}
