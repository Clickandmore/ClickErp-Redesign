<?php

namespace Database\Seeders;

use App\Models\Hourly\HourlyPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Correct the original starter catalog's flat extra-worker price, without rewriting edited prices. */
class FixHourlyStarterWorkerPricesSeeder extends Seeder
{
    public function run(): void
    {
        $updated = DB::transaction(function () {
            $count = 0;
            foreach (['HOURLY-CLEANING'=>[200,1], 'VILLA-CLEANING'=>[600,2],
                'LIVE-IN-SERVICE'=>[400,1], 'OFFICE-CLEANING'=>[350,2]] as $code=>[$price,$workers]) {
                foreach ([1,4] as $visits) {
                    $visitPrice = $visits === 1 ? $price : $price * 0.9;
                    $count += HourlyPackage::where('code', 'STARTER-'.$code.'-'.$visits)
                        ->where('price_per_visit', $visitPrice)->where('workers_per_visit', $workers)
                        ->where('visits_count', $visits)->whereNull('total_price')->where('extra_worker_price', 100)
                        ->update(['extra_worker_price'=>round($visitPrice / $workers, 2)]);
                }
            }
            return $count;
        });
        $this->command?->info("Updated extra-worker prices for {$updated} starter items.");
    }
}
