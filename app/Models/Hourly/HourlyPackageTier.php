<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyPackageTier extends Model {
    protected $guarded=[];
    protected $casts=['is_active'=>'boolean','visits_per_week'=>'float'];
    public function items(){return $this->hasMany(HourlyPackage::class,'package_tier_id');}
    public function getNameAttribute(){return app()->getLocale()==='en' ? ($this->name_en ?: $this->name_ar) : $this->name_ar;}
    public static function defaults():array {
        $rows=[['code'=>'one_off','name_ar'=>'مرة واحدة','name_en'=>'One-off','frequency'=>'one_off','visits_per_week'=>0]];
        for($i=1;$i<=7;$i++)$rows[]=['code'=>'weekly_'.$i,'name_ar'=>['','مرة في الأسبوع','مرتين في الأسبوع','3 مرات في الأسبوع','4 مرات في الأسبوع','5 مرات في الأسبوع','6 مرات في الأسبوع','7 مرات في الأسبوع'][$i],'name_en'=>$i.' times a week','frequency'=>'weekly','visits_per_week'=>$i];
        $rows[]=['code'=>'biweekly','name_ar'=>'كل أسبوعين','name_en'=>'Every two weeks','frequency'=>'biweekly','visits_per_week'=>0.5];
        $rows[]=['code'=>'monthly','name_ar'=>'مرة كل شهر','name_en'=>'Monthly','frequency'=>'monthly','visits_per_week'=>0.25];
        return $rows;
    }
}
