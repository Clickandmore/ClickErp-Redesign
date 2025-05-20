<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::where('group', 'orders')->get();
        return view('admin.ingaz.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'order_expiration_hours' => 'required|integer|min:1|max:720' // الحد الأقصى 30 يوم
        ]);

        Setting::setValue('order_expiration_hours', $request->order_expiration_hours, 'orders');

        return redirect()->back()->with('success', 'تم تحديث الإعدادات بنجاح');
    }
}
