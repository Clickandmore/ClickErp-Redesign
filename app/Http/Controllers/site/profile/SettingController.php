<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\SettingsRequest;
use App\Models\City;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        $cities = City::all();
        return view('site.pages.profile.settings', get_defined_vars());
    }

    public function update(SettingsRequest $request, $id, UserAction $userAction)
    {
        $data = $request->validated();
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $userAction->update($id, $data);
        return jsonSuccess(null , __("ingaz.settings_updated_successfully"));

    }


}
