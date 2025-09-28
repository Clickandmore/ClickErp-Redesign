<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\AboutUsAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\AboutUs as objModel;
use App\Http\Requests\ingaz\AboutUsRequest as objRequest;
use App\Models\ingaz\AboutUs;
use Illuminate\Http\Request;


class AboutUsController extends Controller
{

    public $folderPath = "admin.ingaz.about_us";
    public $postData = ["title:ar", "title:en", "description:ar", "description:en" , "mission:ar", "mission:en", "vision:ar", "vision:en", "value:ar", "value:en"];
    public $mainRoute = "about-us";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action)
    {
         $about = AboutUs::first();
         return view('admin.ingaz.about_us.index', compact('about'));
    }


    public function create(Request $request)
    {
        if ($request->ajax() ) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, objAction $action)
    {
        $data = $request->only($this->postData);
        $data = $action->store($data);
        return jsonSuccess($data , null , 201);
    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
         $dataInsert = $request->only($this->postData);

        // Get the first setting record or create one if it doesn't exist
       $setting = AboutUs::findOrFail($id);


        $action->updateSetting($setting->id, $dataInsert);
        return jsonSuccess(null , null , 201);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
