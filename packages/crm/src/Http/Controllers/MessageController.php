<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nami\Crm\Models\MessageModel as ObjModel;
use Yajra\DataTables\DataTables;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public $folderPath = "crm::crm.messages_reports";
    public $postData = ['title','messages stie'];
    public $mainRoute = "crm.messages-reports";

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dataTable = objModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->editColumn('email', function ($row) {
                    return $row->email;
                })
                ->editColumn('message', function ($row) {
                    return $row->message;
                })->addColumn('actions', function ($row) {
                    return deleteButton(route($this->mainRoute . ".destroy", $row->id));
                })->escapeColumns([''])->make(true);
        }
        $data["bladeTitle"] = __('menu.messages');

        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjModel $messageModel)
    {
        $messageModel->find($id)->delete();
        return jsonSuccess();
    }
}
