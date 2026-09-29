<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncMoyasarRefunds extends Command
{
    protected $signature = 'moyasar:sync-refunds {--invoice= : Reconcile one stored invoice ID}';
    protected $description = 'Reconcile stored Moyasar invoices and missed refund notifications';
    public function handle(): int
    {
        $count=0; $failed=0; $seen=[];
        $contracts=app(\App\Services\MoyasarContractPaymentService::class);
        foreach ([[\App\Models\RentalContractInfo::class,'moyasar_payment_id'],[\App\Models\RentalContractExpiryReminder::class,'payment_id'],[\App\Models\MoyasarContractPayment::class,'invoice_id']] as [$model,$column]) {
            $query=$model::whereNotNull($column);
            if($this->option('invoice'))$query->where($column,$this->option('invoice'));
            $query->chunkById(100,function($rows) use($column,$contracts,&$count,&$failed,&$seen){
                foreach($rows as $row){
                    $ref=$row->$column;if(isset($seen[$ref]))continue;$seen[$ref]=true;
                    try {$payment=$contracts->resolve($ref);if($payment){$contracts->synchronize($payment);$count++;}}
                    catch(\Throwable $e){report($e);$failed++;$this->error('Contract invoice '.$ref.' failed; see application log.');}
                }
            });
        }
        foreach ([[\App\Models\RentalOrderPayment::class,'provider_invoice_id',\App\Services\RentalOrderPaymentService::class],[\App\Models\Hourly\HourlyOrderPayment::class,'provider_reference',\App\Services\Hourly\HourlyPaymentService::class]] as [$model,$column,$service]) {
            $query=$model::where('provider','moyasar')->whereNotNull($column);
            if($this->option('invoice'))$query->where($column,$this->option('invoice'));
            $query->chunkById(100,function($rows)use($service,&$count,&$failed){
                foreach($rows as $row){try{app($service)->synchronize($row);$count++;}catch(\Throwable $e){report($e);$failed++;$this->error(class_basename($row).' #'.$row->id.' failed; see application log.');}}
            });
        }
        $this->info("Synchronized: {$count}; failed: {$failed}");
        return $failed ? 1 : 0;
    }
}