<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\ingaz\ContractStatusEnum;
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
    protected $description = 'Update warranty status for arrival contracts and close them after warranty expiry';

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

        // جلب العقود في مرحلة الوصول أو المكتملة التي قد تحتاج إعادة تقييم
        $contracts = Contract::whereIn('status', [
                ContractStatusEnum::Arrival->value,
                ContractStatusEnum::Completed->value,
            ])
            ->where(function ($query) {
                $query->where('warranty_status', 'active')
                    ->orWhereNull('warranty_status');
            })
            ->get();

        $updatedCount = 0;
        $expiredCount = 0;

        foreach ($contracts as $contract) {
            // البحث عن رحلة الوصول للعقد
            $travelHistory = TravelHistory::where('order_id', $contract->id)
                ->latest('id')
                ->first();

            if ($travelHistory) {
                $arrivalDateTime = null;
                if ($travelHistory->arrival_date && $travelHistory->arrival_time) {
                    $arrivalDateTime = Carbon::parse($travelHistory->arrival_date . ' ' . $travelHistory->arrival_time);
                } elseif ($travelHistory->arrival_date) {
                    $arrivalDateTime = Carbon::parse($travelHistory->arrival_date);
                } elseif (!empty($travelHistory->arrived_at)) {
                    $arrivalDateTime = Carbon::parse($travelHistory->arrived_at);
                }

                if (!$arrivalDateTime) {
                    $contract->update(['warranty_status' => 'not_applicable']);
                    $this->warn("Contract {$contract->id}: No arrival datetime found, warranty set to not applicable");
                    continue;
                }

                $warrantyEndDate = $arrivalDateTime->copy()->addDays(90);

                $updates = [
                    'warranty_start_date' => $arrivalDateTime,
                    'warranty_end_date' => $warrantyEndDate
                ];

                // التحقق من انتهاء الضمان
                if ($warrantyEndDate->isPast()) {
                    $updates['status'] = ContractStatusEnum::Completed->value;
                    $updates['warranty_status'] = 'expired';
                    $expiredCount++;
                    $this->warn("Contract {$contract->id}: Warranty expired on {$warrantyEndDate->format('Y-m-d')}");
                } else {
                    $updates['status'] = ContractStatusEnum::Arrival->value;
                    $updates['warranty_status'] = 'active';
                    $this->info("Contract {$contract->id}: Warranty active until {$warrantyEndDate->format('Y-m-d')}");
                }

                $contract->update($updates);

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
