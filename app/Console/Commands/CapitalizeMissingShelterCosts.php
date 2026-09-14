<?php

namespace App\Console\Commands;

use App\Services\ingaz\ShelterDirectEntryCostService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CapitalizeMissingShelterCosts extends Command
{
    protected $signature = 'finance:capitalize-missing-shelter-costs
        {contracts* : أرقام العقود المطلوب معالجتها فقط}
        {--apply : إنشاء قيود نقل التكلفة، بدونه معاينة للقراءة فقط}
        {--expected-database= : اسم قاعدة البيانات المقصودة، مطلوب عند التنفيذ}';

    protected $description = 'نقل تكلفة المورد المثبتة إلى دفتر الإيواء للعقود التي دخلت مباشرة دون تكلفة إيواء؛ لا يسجل استرداداً';

    public function handle(ShelterDirectEntryCostService $service): int
    {
        $database = DB::connection()->getDatabaseName();
        $apply = (bool) $this->option('apply');
        $this->line('قاعدة البيانات: ' . $database . ($apply ? ' — تنفيذ' : ' — معاينة فقط'));
        if ($apply && $this->option('expected-database') !== $database) {
            $this->error('يلزم تحديد اسم القاعدة الصحيح باستخدام --expected-database.');
            return self::FAILURE;
        }
        $ids = array_unique($this->argument('contracts'));
        foreach ($ids as $id) {
            if (!ctype_digit((string) $id) || (int) $id <= 0) {
                $this->error('أرقام العقود يجب أن تكون أعداداً صحيحة موجبة.');
                return self::FAILURE;
            }
        }
        $failed = false;
        foreach ($ids as $id) {
            try {
                $result = $service->reconcile((int) $id, $apply);
                $this->line('#' . $id . ': ' . $result);
            } catch (\Throwable $e) {
                $failed = true;
                $this->error('#' . $id . ': ' . $e->getMessage());
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }

}
