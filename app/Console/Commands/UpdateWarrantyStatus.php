<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\TravelHistory;
use Carbon\Carbon;

class UpdateWarrantyStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update-warranty-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update warranty status for completed contracts based on arrival date';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting warranty status update...');

        // جلب العقود المكتملة التي تحت الضمان
        $contracts = Contract::where('status', 'completed')
            ->where('warranty_status', 'active')
            ->get();

        $updatedCount = 0;
        $expiredCount = 0;

        foreach ($contracts as $contract) {
            // البحث عن رحلة الوصول للعقد
            $travelHistory = TravelHistory::where('order_id', $contract->id)
                ->whereNotNull('arrival_date')
                ->whereNotNull('arrival_time')
                ->first();

            if ($travelHistory) {
                // حساب تاريخ انتهاء الضمان (90 يوم من تاريخ الوصول)
                $arrivalDateTime = Carbon::parse($travelHistory->arrival_date . ' ' . $travelHistory->arrival_time);
                $warrantyEndDate = $arrivalDateTime->copy()->addDays(90);

                // تحديث تواريخ الضمان في العقد
                $contract->update([
                    'warranty_start_date' => $arrivalDateTime,
                    'warranty_end_date' => $warrantyEndDate
                ]);

                // التحقق من انتهاء الضمان
                if ($warrantyEndDate->isPast()) {
                    $contract->update(['warranty_status' => 'expired']);
                    $expiredCount++;
                    $this->warn("Contract {$contract->id}: Warranty expired on {$warrantyEndDate->format('Y-m-d')}");
                } else {
                    $this->info("Contract {$contract->id}: Warranty active until {$warrantyEndDate->format('Y-m-d')}");
                }

                $updatedCount++;
            } else {
                // إذا لم توجد رحلة، تعيين حالة الضمان كغير قابل للتطبيق
                $contract->update(['warranty_status' => 'not_applicable']);
                $this->warn("Contract {$contract->id}: No travel history found, warranty set to not applicable");
            }
        }

        $this->info("Warranty status update completed!");
        $this->info("Total contracts processed: {$updatedCount}");
        $this->info("Expired warranties: {$expiredCount}");

        return 0;
    }
}
