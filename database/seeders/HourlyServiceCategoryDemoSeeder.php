<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Hourly\HourlyServiceCategory;
class HourlyServiceCategoryDemoSeeder extends Seeder {
    public function run(): void {
        foreach([['hourly-cleaning','خدمة كل ساعة','Hourly cleaning'],['villa-cleaning','تنظيف الفلل','Villa cleaning'],['office-cleaning','تنظيف المكاتب','Office cleaning']] as $i=>[$slug,$ar,$en]){
            HourlyServiceCategory::firstOrCreate(['slug'=>$slug],['name_ar'=>$ar,'name_en'=>$en,'icon_type'=>'library','icon'=>'fa-broom','color'=>'#6861ee','sort_order'=>$i+1,'is_active'=>false]);
        }
    }
}
