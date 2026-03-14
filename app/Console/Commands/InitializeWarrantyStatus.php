<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\TravelHistory;
use Carbon\Carbon;

class InitializeWarrantyStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:initialize-warranty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize warranty status for arrival and completed contracts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Initializing warranty status for arrival and completed contracts...');

        // جلب جميع العقود في الوصول أو المكتملة
        $contracts = Contract::whereIn('status', [
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Completed->value,
        ])->get();

        $activeCount = 0;
        $expiredCount = 0;
        $notApplicableCount = 0;

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
                    $contract->warranty_status = 'not_applicable';
                    $notApplicableCount++;
                    $this->warn("Contract {$contract->id}: No arrival datetime found, warranty set to not applicable");
                    $contract->save();
                    continue;
                }

                $warrantyEndDate = $arrivalDateTime->copy()->addDays(90);

                // تحديث تواريخ الضمان في العقد
                $contract->warranty_start_date = $arrivalDateTime;
                $contract->warranty_end_date = $warrantyEndDate;

                // التحقق من انتهاء الضمان
                if ($warrantyEndDate->isPast()) {
                    $contract->status = ContractStatusEnum::Completed->value;
                    $contract->warranty_status = 'expired';
                    $expiredCount++;
                    $this->warn("Contract {$contract->id}: Warranty expired on {$warrantyEndDate->format('Y-m-d')}");
                } else {
                    $contract->status = ContractStatusEnum::Arrival->value;
                    $contract->warranty_status = 'active';
                    $activeCount++;
                    $this->info("Contract {$contract->id}: Warranty active until {$warrantyEndDate->format('Y-m-d')}");
                }
            } else {
                // إذا لم توجد رحلة وصول، نحاول البحث بطرق أخرى

                // محاولة الحصول على تاريخ الوصول من معلومات العقد
                if ($contract->info && $contract->info->arrival_date) {
                    $arrivalDate = Carbon::parse($contract->info->arrival_date);
                    $warrantyEndDate = $arrivalDate->copy()->addDays(90);

                    $contract->warranty_start_date = $arrivalDate;
                    $contract->warranty_end_date = $warrantyEndDate;

                    if ($warrantyEndDate->isPast()) {
                        $contract->status = ContractStatusEnum::Completed->value;
                        $contract->warranty_status = 'expired';
                        $expiredCount++;
                        $this->warn("Contract {$contract->id}: Warranty expired (from contract info) on {$warrantyEndDate->format('Y-m-d')}");
                    } else {
                        $contract->status = ContractStatusEnum::Arrival->value;
                        $contract->warranty_status = 'active';
                        $activeCount++;
                        $this->info("Contract {$contract->id}: Warranty active (from contract info) until {$warrantyEndDate->format('Y-m-d')}");
                    }
                } else {
                    // إذا لم نجد أي تاريخ وصول، نفترض أن الضمان انتهى إذا مر أكثر من 90 يوم على إكمال العقد
                    $completedDate = $contract->updated_at;
                    $daysSinceCompleted = $completedDate->diffInDays(now());

                    if ($daysSinceCompleted > 90) {
                        $contract->status = ContractStatusEnum::Completed->value;
                        $contract->warranty_status = 'expired';
                        $expiredCount++;
                        $this->warn("Contract {$contract->id}: Warranty expired (no arrival date, completed {$daysSinceCompleted} days ago)");
                    } else {
                        // نفترض أن الضمان نشط مؤقتاً
                        $contract->status = ContractStatusEnum::Arrival->value;
                        $contract->warranty_status = 'active';
                        $contract->warranty_start_date = $completedDate;
                        $contract->warranty_end_date = $completedDate->copy()->addDays(90);
                        $activeCount++;
                        $this->info("Contract {$contract->id}: Warranty active (estimated from completion date)");
                    }
                }
            }

            $contract->save();
        }

        $totalProcessed = $activeCount + $expiredCount + $notApplicableCount;

        $this->info("Warranty status initialization completed!");
        $this->info("Total contracts processed: {$totalProcessed}");
        $this->info("Active warranties: {$activeCount}");
        $this->info("Expired warranties: {$expiredCount}");
        $this->info("Not applicable: {$notApplicableCount}");

        return 0;
    }
}
