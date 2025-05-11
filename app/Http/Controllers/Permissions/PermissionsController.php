<?php

namespace App\Http\Controllers\Permissions;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class PermissionsController extends Controller
{

    public function setPerms($id){
        $role = Role::find($id);
        $data = Permission::where('guard_name', 'admin')->get();
        $getPermissionsGroups = Permission::where('guard_name', 'admin')
            ->groupBy('group_name')
            ->pluck('group_name')
            ->all();
        //BUILD ARRAY--------------------
        $buildPermissionsArray = [];
        if($data){
            foreach($data as $k => $singleRow){
                try{
                    $singleRow = $singleRow->toArray();
                    $groupName = $singleRow['group_name'];
                    $sectionName = $singleRow['section_name'];
                    $buildPermissionsArray[$sectionName][$groupName][] = $singleRow;
                }catch(\Exception $e){
                    continue;
                }
            }
        }
        $data = $buildPermissionsArray;
        //dd($data);
        //END BUILD ARRAY--------------------
        $availableRoles = Role::all();
        if($role)
            return view('admin.permissions.perm_control.control',compact('role','data','availableRoles'));
        return redirect(route('dash.roles.index'));
    }

    public function storePerms(Request $request){
        if(isset($request->role_id)){
            $role = Role::find($request->role_id);
            $permsArr = $request->perms ?? [];
            //dd($permsArr);
            $assignPerms = $role->syncPermissions($permsArr);
            return redirect()->back()->with('msg',__('admin.success'));
        }
        return redirect(route('dash.roles.index'));
    }

    public function index($id = null){


        $data = Permission::all();
        $editData = null;
        if($id != null){
            $editData = Permission::find($id);
        }
        return view('admin.permissions.perm_keys.index',compact('data','editData'));
    }

    public function store(Request $request){
        $id = null;
        if(isset($request->edit_id)){
            $id = $request->edit_id;
        }
        $rules = ['name' => 'required|unique:permissions'.($id ? ',id' .($id ? ",$id" : '') : '')];
        $validator = Validator::make($request->all(),$rules);
        if($validator->fails())
            return redirect()->back()->with('error',__('admin.roles_name_validation'));

        $perm = Permission::updateOrCreate([
                'id' => $id
            ],[
                'name' => $request->name,
                'group_name' => $request->group_name,
                'section_name' => $request->section_name,
                'guard_name' => 'admin',
        ]);

        return redirect(route('dash.developers.perms.index'))->with('msg',__('admin.success'));
    }

    public function delete(Request $request){
        $perm = Permission::find($request->id);
        if($perm){
            $perm->delete();
        }
        return redirect()->back();
    }

}
