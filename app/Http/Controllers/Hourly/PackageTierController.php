<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyPackageTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PackageTierController extends Controller {
    public function index(){return view('admin.hourly.package-tiers',['tiers'=>HourlyPackageTier::withCount('items')->orderBy('sort_order')->orderBy('id')->get()]);}
    public function update(Request $request,HourlyPackageTier $tier){
        $data=$request->validate(['name_ar'=>['required','string','max:255'],'name_en'=>['nullable','string','max:255'],'is_active'=>['required','boolean'],'sort_order'=>['required','integer','between:0,10000'],'code'=>['prohibited'],'frequency'=>['prohibited'],'visits_per_week'=>['prohibited']]);
        $tier->update($data);return back()->with('success',__('hourly.tier_saved'));
    }
}
