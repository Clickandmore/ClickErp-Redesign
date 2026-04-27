<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\AccountRequest as ObjRequest;
use Nami\Finance\Services\AccountService as ObjService;

class ChildCodeController extends Controller
{
    public function index(Request $request,ObjService $service)
    {
        //
    }


    public function create(Request $request, ObjService $service)
    {
      //
    }


    public function store(ObjRequest $request, ObjService $service)
    {
        //
    }


    public function show($id,ObjService $service)
    {
        $parentPluckCode = $service->parentPluckCode();
        $childPluckCode = $service->childPluckCode($id);
        return $service->getChildCode($id,$childPluckCode,$parentPluckCode);
    }


    public function edit(int $id, Request $request, ObjService $service)
    {
       //
    }


    public function update(ObjRequest $request, int $id, ObjService $service)
    {
      //
    }


    public function destroy(int $id, ObjService $service)
    {
        //
    }
}
