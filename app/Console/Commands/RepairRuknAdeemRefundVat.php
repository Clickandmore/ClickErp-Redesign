<?php

namespace App\Console\Commands;

use App\Services\RuknAdeemRefundVatRepair;
use Illuminate\Console\Command;
use Throwable;

class RepairRuknAdeemRefundVat extends Command
{
    protected $signature = 'finance:repair-rukn-adeem-refund-vat {--apply : Apply the four corrections} {--admin-id= : Existing administrator ID for the audit trail}';
    protected $description = 'Preview or repair four audited refund VAT lines in ruknadem_dashboard only';

    public function handle(RuknAdeemRefundVatRepair $repair): int
    {
        try {
            $admin = $this->option('admin-id');
            if ($admin !== null && (!ctype_digit((string) $admin) || (int) $admin < 1)) {
                throw new \RuntimeException('--admin-id must be a positive integer.');
            }
            $result = $repair->run((bool) $this->option('apply'), $admin === null ? null : (int) $admin);
            $this->info(json_encode($result, JSON_UNESCAPED_UNICODE));
            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
