<?php

namespace App\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{
    public function index($id = null){
        $roles = Role::all();
        $editRole = null;
        if($id != null){
            $editRole = Role::find($id);
        }
        return view('admin.permissions.roles.index',compact('roles','editRole'));
    }

    public function store(Request $request)
    {
        $id = $request->edit_id;
        $rules = [
            'name' => 'required|unique:roles,name' . ($id ? ',' . $id : '')
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->with('error', __('admin.roles_name_validation'));
        }

        $role = Role::updateOrCreate(['id' => $id], [
            'name' => $request->name,
            'guard_name' => 'admin',
        ]);

        return redirect(route('dash.roles.index'))->with('success', __('admin.success'));
    }


    public function delete(Request $request){
        $role = Role::find($request->id);
        if($role){
            ///////////////
            //MUST SWITCH ALL USERS ON THIS ROLE TO A DEFAULT ROLE
            //BEFORE DELETION
            ///////////////
            $role->delete();
        }
        return redirect()->back();
    }


}
