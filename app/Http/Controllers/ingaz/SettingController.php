<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Http\Actions\dashboard\SettingAction as objAction;
use App\Models\Setting;

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
        $settings = Setting::where('group', 'orders')->get();
        return view('admin.ingaz.settings.index', compact('settings'));
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

    public function update(Request $request)
    {
        $request->validate([
            'order_expiration_hours' => 'required|integer|min:1|max:720',
            'warrenty_period' => 'nullable|integer|min:1',
            'bank_transfer_data' => 'nullable|string',
            'sadad_transfer_data' => 'nullable|string',
            'show_popup' => 'nullable|boolean',
            'popup_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // تحديث مدة الحجز
        Setting::setValue('order_expiration_hours', $request->order_expiration_hours, 'orders');

        // تحديث فترة الضمان
        if ($request->has('warrenty_period')) {
            Setting::setValue('warrenty_period', $request->warrenty_period, 'orders');
        }

        // تحديث بيانات التحويل البنكي
        if ($request->has('bank_transfer_data')) {
            Setting::setValue('bank_transfer_data', $request->bank_transfer_data, 'payment');
        }

        // تحديث بيانات تحويل سداد
        if ($request->has('sadad_transfer_data')) {
            Setting::setValue('sadad_transfer_data', $request->sadad_transfer_data, 'payment');
        }

        // تحديث إعدادات النافذة المنبثقة
        Setting::setValue('show_popup', $request->show_popup ? '1' : '0', 'popup');

        // معالجة صورة النافذة المنبثقة
        if ($request->hasFile('popup_image')) {
            $path = $request->file('popup_image')->store('public/settings');
            Setting::setValue('popup_image', $path, 'popup');
        }

        return redirect()->back()->with('success', __('admin.settings_updated_successfully'));
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
