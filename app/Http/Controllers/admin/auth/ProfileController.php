<?php

namespace App\Http\Controllers\admin\auth;

use App\Http\Actions\auth\ProfileAction as ObjAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\auth\profile\UpdateProfileRequest as UpdateRequest;
use Illuminate\Http\Request;


class ProfileController extends Controller
{
    public $folderPath = "admin.auth.profile";
    public $mainRoute = "profile";
    public $postData = ["name", "email", "phone", 'image'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(ObjAction $action)
    {
        $data["user"] = $action->getAuthUser();
        $data["bladeTitle"] = __("auth.profile");
        return view('admin.auth.profile', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function store(/*StoreRequest $request,*/ ObjAction $action)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    public function edit($id, Request $request, ObjAction $action)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function update(UpdateRequest $request, $id, ObjAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateProfile($id, $dataInsert);
        return jsonSuccess($data, 'تم تعديل البيانات بنجاح',201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjAction $action)
    {

    }
}
