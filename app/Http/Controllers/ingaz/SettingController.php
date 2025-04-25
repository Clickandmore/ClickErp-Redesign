<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Http\Actions\dashboard\SettingAction as objAction;

use App\Http\Requests\ingaz\SettingRequest;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public $folderPath = "admin.ingaz.settings";
    public $postData = ['logo_header','logo_footer','favicon','twitter','facebook','instagram','snapchat','linkedin',
    'youtube','whatsapp','email','phone','other_phone','map_link',
    'location:ar','location:en','copyright:ar','copyright:en','terms_conditions:ar','terms_conditions:en','about_us_app:ar','about_us_app:en',
    'website_name:ar','website_name:en','privacy:ar','privacy:en',
        "about_image","about_us_sub_title:ar","about_us_sub_title:en","footer_text:ar","footer_text:en",
    "rights_maids:ar","rights_maids:en","rights_users:ar","rights_users:en","return_conditions:ar","return_conditions:en"];
    public $mainRoute = "settings";

    function __construct()
    {
        // $this->middleware('permission:الإعدادات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الإعدادات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الإعدادات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الإعدادات-حذف,admin', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $objAction)
    {

        $data = [
            "bladeTitle" => __('skaeeb.settings'),
            "addButtonText" => __('skaeeb.settings'),
            'settings' => $objAction->getSetting(),
        ];

        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SettingRequest $request, objAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeSetting($dataInsert,$request);
        return jsonSuccess($data,null,201);
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
    public function edit($id, Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $action->find($id)
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(SettingRequest $request, $id, ObjAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateSetting($id, $dataInsert);
        return jsonSuccess($data,null,201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjAction $action)
    {
        $action->deleteSetting($id);
        return jsonSuccess();
    }
}
