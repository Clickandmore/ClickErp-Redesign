<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserAction extends MainAction
{
    public function __construct(User $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/User/';
    }

    public function storeUser($data)
    {
        $data['password'] = bcrypt($data['password']) ;
        return $this->store($data);
    }

    public function updateUser($id, $data)
    {
        $obj = $this->find($id);
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        if (isset($requset->new_password)) {
            if (Hash::check($requset->old_password, $obj->password)) {
                $obj->update([
                    'password' => bcrypt($requset->new_password)
                ]);
            } else {
                return false;
            }
        }
       return $this->find($id)->update( $data);
    }

    public function updatePassword($request) {
        $user = $this->find(Auth::user()->id);
        if(Hash::check($request->old_password,$user->password)) {
            $user->update([
                'password' => bcrypt($request->new_password)
            ]);
            return true;
        } else {
            return false;
        }
    }

    public function getCounts(){
        $allCount = $this->model->count();
        $thisMonthCount = $this->model
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
        return [
            'allCount' => $allCount,
            'thisMonthCount' => $thisMonthCount,
        ];
    }
}
