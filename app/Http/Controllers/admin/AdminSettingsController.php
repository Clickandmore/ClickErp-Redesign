<?php

namespace App\Http\Controllers\Admin;

use App\Models\NewSettings;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminSettingsController extends Controller
{

    public function index()
    {
        $fields = [
            'warrenty_period',
            'bank_transfer_data',
            'sadad_transfer_data',
        ];

        $settings = new NewSettings();
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $settings->getSetting($field);
        }

        return view('admin.settings.index', compact('data', 'fields'));
    }

    public function store(Request $request)
    {
        $fields = [
            'warrenty_period',
            'bank_transfer_data',
            'sadad_transfer_data',
        ];

        $settings = new NewSettings();

        foreach ($fields as $field) {
            if (isset($request->$field)) {
                $value = $request->$field ? $request->$field : '';
                $settings->setSetting($field, $value);
            }
        }
        return redirect()->back();
    }


}
