<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\ItemService as ObjService;

class LoadBladeEditViewController extends Controller
{
    public string $folderPath = "finance::finance.items.edit";

    public function show($type, ObjService $service,$id)
    {
        $data = $service->getEditData($id);
        switch ($type) {
            case 'service':
                return view($this->folderPath . '.service', $data);
            case 'stock':
                return view($this->folderPath . '.stock', $data);
            case 'expired':
                return view($this->folderPath . '.expired', $data);
            case 'boxs':
                return view($this->folderPath . '.boxs', $data);
            case 'raw_materials':
                return view($this->folderPath . '.raw_materials', $data);
            case 'finished_product':
                return view($this->folderPath . '.finished_product', $data);
            default:
                return response()->json(['error' => 'Invalid type'], 404);
        }
    }

}
