<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hourly\CategorySettingsRequest;
use App\Models\Hourly\HourlyPackage;
use App\Services\Hourly\{HourlyCategorySettings,ServiceItemService};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\DB;
class ServiceSettingsController extends Controller {
    public function edit(HourlyPackage $package,HourlyCategorySettings $settings) {
        $package->loadMissing('serviceCategory');
        return view('admin.hourly.service-settings',['package'=>$package,'heading'=>__('hourly.item_settings.title'),'subheading'=>__('hourly.item_settings.subtitle',['category'=>$package->serviceCategory?->name_ar ?? '—']),'saveTitle'=>__('hourly.item_settings.save'),'settingsAction'=>route('admin.hourly-service-items.settings.update',$package),'categorySettings'=>array_replace($package->settings_overrides??[],['tax_rate'=>$package->tax_rate]),'generalSettings'=>$settings->resolved($package->service_category_id),'canEdit'=>true]);
    }
    public function update(CategorySettingsRequest $request,HourlyPackage $package,ServiceItemService $audit) {
        DB::transaction(function()use($request,$package,$audit){$package=HourlyPackage::lockForUpdate()->findOrFail($package->id);$before=$audit->snapshot($package);$data=$request->settings();$package->tax_rate=$data['tax_rate']??null;unset($data['tax_rate'],$data['tracking_enabled']);$package->settings_overrides=$data;$package->revision++;$package->save();$audit->audit($package,'edit',$before);});
        return back()->with('success','تم حفظ تخصيص العنصر.');
    }
    public function defaults(HourlyCategorySettings $settings) {
        $general=[];foreach(HourlyCategorySettings::GENERAL as $key=>[$type,$value])$general[$key]=$key==='working_days'?collect(HourlyCategorySettings::WEEKDAYS)->mapWithKeys(fn($label,$day)=>[$day=>['is_on'=>!in_array($day,HourlyRentalSettings::get('closed_weekdays',[])),'order_buffer_hours'=>0]])->all():$value;
        return view('admin.hourly.service-settings',['heading'=>__('hourly.item_settings.defaults_title'),'subheading'=>__('hourly.item_settings.defaults_subtitle'),'saveTitle'=>__('hourly.item_settings.defaults_save'),'settingsAction'=>route('admin.hourly-service-defaults.update'),'categorySettings'=>HourlyRentalSettings::get('service_defaults',[]),'generalSettings'=>$general,'canEdit'=>true]);
    }
    public function saveDefaults(CategorySettingsRequest $request) {
        $data=$request->settings();unset($data['tracking_enabled']);
        HourlyRentalSettings::persist(['service_defaults'=>$data],auth('admin')->id());
        return back()->with('success','تم حفظ القيم العامة للخدمات.');
    }
}
