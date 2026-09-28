<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Hourly\HourlyPackageTier;
class HourlyPackageTiersSeeder extends Seeder {
    public function run():void {foreach(HourlyPackageTier::defaults() as $sort=>$row)HourlyPackageTier::firstOrCreate(['code'=>$row['code']],$row+['is_active'=>true,'sort_order'=>$sort]);}
}
