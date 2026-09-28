<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyPackageLog extends Model {
    protected $guarded=[];
    protected $casts=['before_data'=>'array','after_data'=>'array'];
    public function changes(): array {
        $labels=['id'=>'رقم العنصر','code'=>'الرمز','title'=>'الاسم','category'=>'فئة الخدمة','package_type'=>'نوع الباقة','brand'=>'العلامة','total_price'=>'إجمالي السعر','price_per_visit'=>'سعر الزيارة','hours_per_visit'=>'الساعات','duration_minutes'=>'مدة الزيارة بالدقائق','visits_count'=>'الزيارات','workers_per_visit'=>'العاملات','validity_days'=>'الصلاحية بالأيام','extra_worker_price'=>'العاملة الإضافية','extra_hour_price'=>'الساعة الإضافية','transport_fee'=>'رسوم النقل','transport_included'=>'النقل مشمول','tax_rate'=>'الضريبة','deposit_amount'=>'الدفعة المقدمة','monthly_amount'=>'القسط الشهري','break_minutes'=>'الاستراحة بالدقائق','buffer_minutes'=>'الفاصل بالدقائق','recurring_months'=>'تكرار الشهور','start_times'=>'أوقات البداية','is_active'=>'الحالة','is_private'=>'خاصة','materials_included'=>'المواد مشمولة','for_home'=>'للمنازل','for_company'=>'للشركات','show_on_app'=>'الظهور في التطبيق','ignore_closed_days'=>'تجاهل الإغلاق','sort_order'=>'الترتيب','min_notice_hours'=>'مهلة الحجز','cancellation_window_hours'=>'مهلة الإلغاء','cancellation_fee_percent'=>'رسوم الإلغاء %','cities'=>'المدن','nationalities'=>'الجنسيات','title_ar'=>'الاسم العربي','title_en'=>'الاسم الإنجليزي','description_ar'=>'الوصف العربي','description_en'=>'الوصف الإنجليزي'];
        $labels['attribute_id']='سمة المنتج';
        $labels['package_tier_id']='باقة التكرار';
        $labels['service_category_id']='رقم فئة الخدمة';
        $labels['settings_overrides']='تخصيص إعدادات الفئة';
        $format=function($key,$value){
            if($value===null)return '—';if(is_bool($value))return $value?'نعم':'لا';
            if($key==='start_times')return collect($value)->map(fn($access,$time)=>$time.' ('.($access==='all'?'الجميع':'التشغيل فقط').')')->implode('، ') ?: 'بدون مواعيد';
            if($key==='settings_overrides')return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            return is_array($value)?implode('، ',$value):(string)$value;
        };
        $changes=[];foreach($labels as $key=>$label){$before=$this->before_data[$key]??null;$after=$this->after_data[$key]??null;if($before!==$after)$changes[]=['label'=>$label,'before'=>$format($key,$before),'after'=>$format($key,$after)];}return $changes;
    }
}
