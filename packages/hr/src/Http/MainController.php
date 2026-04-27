<?php

/*
@copyright

Al-Faisal Transport System V.1

Copyright (C) 2023 <https://alfaisaltransport.com/> All rights reserved.
Design and developed by Al-Faisal Transport <https://alfaisaltransport.com/>

 */
namespace Nami\Hr\Http;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public $folderPath ;
    public $mainRoute;
    public $action;
    public $request;

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->action->createData($this->mainRoute);
            $returnHTML = view($this->folderPath . ".create")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }



       /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $dataInsert = $this->request->validated();
        $data = $this->action->customStore($dataInsert);
        return jsonSuccess($data);
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request,$id)
    {
        if ($request->ajax()) {
            $data = $this->action->showData($id);
            $returnHTML = view( $this->folderPath.'.show')->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id, Request $request)
    {
        if ($request->ajax()) {
            $data = $this->action->editData($id,$this->mainRoute);
            $returnHTML = view($this->folderPath . ".edit")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update( $id)
    {
        $dataInsert = $this->request->validated();
        $data = $this->action->customUpdate($id, $dataInsert);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->action->delete($id);
        return jsonSuccess();
    }
}
