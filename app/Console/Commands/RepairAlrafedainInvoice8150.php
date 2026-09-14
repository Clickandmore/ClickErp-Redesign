<?php

namespace App\Console\Commands;

use App\Models\ingaz\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairAlrafedainInvoice8150 extends Command
{
    protected $signature = 'invoices:repair-alrafedain-8150 {--apply} {--expected-database=}';
    protected $description = 'تصحيح بنود فاتورة الرافدين 8150 من القيد الأصلي 82565 دون تعديل القيود أو الإجماليات';

    public function handle(): int
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== 'alrafedain_dashboard' || $this->option('expected-database') !== $database) {
            $this->error('هذا الأمر مخصص لقاعدة alrafedain_dashboard ويجب تحديدها في --expected-database.');
            return self::FAILURE;
        }
        try {
            DB::transaction(function () {
                $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail(8150);
                $source = DB::table('fn_constraints')->where('id', 82565)->whereNull('deleted_at')->lockForUpdate()->first();
                if (!$source || (int) $invoice->contract_id !== 7723
                    || (int) $invoice->legacy_invoice_number !== 8150 || (string) $invoice->legacy_order_number !== '2601'
                    || (int) $invoice->invoice_constraint_id !== 82565 || $invoice->revenue_recognition_mode !== 'on_invoice'
                    || $source->statement !== 'فاتورة: 8150' || $source->date !== '2026-07-02'
                    || (float) $source->value !== 12000.8 || (float) $invoice->total_due !== 12000.8
                    || (float) $invoice->subtotal !== 11378.0 || (float) $invoice->vat_amount !== 622.8
                    || (float) $invoice->discount !== 0.0 || (float) $invoice->internal_ticket_cost !== 0.0
                    || $invoice->line_items) {
                    throw new \RuntimeException('بيانات الفاتورة أو القيد تختلف عن الحالة المعتمدة؛ لم ينفذ التصحيح.');
                }
                $lines = DB::table('fn_constraint_details')->where('constraint_id', 82565)
                    ->whereNull('deleted_at')->orderBy('id')->lockForUpdate()->get();
                $credits = $lines->where('creditor_value', '>', 0)->map(fn ($line) => [(int) $line->account_id, (float) $line->creditor_value])->values()->all();
                if ($credits !== [[423, 4152.0], [423, 6786.0], [428, 440.0], [114, 622.8]]
                    || round((float) $lines->sum('debtor_value'), 2) !== 12000.8
                    || round((float) $lines->where('account_id', 1510)->sum('debtor_value'), 2) !== 12000.8
                    || (float) $lines->where('account_id', 1510)->sum('creditor_value') !== 0.0) {
                    throw new \RuntimeException('تفاصيل القيد الأصلي غير مطابقة للتوزيع المعتمد.');
                }
                $after = ['direct_recruitment_cost' => 4152, 'direct_recruitment_count' => 1,
                    'external_recruitment_cost' => 6786, 'external_recruitment_count' => 1,
                    'government_cost' => 440, 'government_cost_count' => 1];
                $before = $invoice->only(array_keys($after));
                $alreadyCorrect = collect($after)->every(fn ($value, $key) => (float) $invoice->$key === (float) $value);
                if (!$alreadyCorrect && ((float) $invoice->direct_recruitment_cost !== 11378.0
                    || (int) $invoice->direct_recruitment_count !== 1
                    || (float) $invoice->external_recruitment_cost !== 0.0
                    || (float) $invoice->government_cost !== 0.0)) {
                    throw new \RuntimeException('الفاتورة معدلة سابقاً بتوزيع مختلف؛ أوقف التنفيذ للمراجعة.');
                }
                $backup = null;
                if ($this->option('apply') && !$alreadyCorrect) {
                    $directory = storage_path('app/legacy-invoice-links');
                    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
                        throw new \RuntimeException('تعذر إنشاء مجلد النسخ الاحتياطية.');
                    }
                    $backup = $directory . '/8150-breakdown-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json';
                    $data = ['database' => DB::connection()->getDatabaseName(), 'before' => $invoice->getAttributes(), 'source_lines' => $lines, 'after' => $after];
                    if (file_put_contents($backup, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) === false) {
                        throw new \RuntimeException('تعذر حفظ النسخة الاحتياطية.');
                    }
                    $invoice->update($after);
                }
                $this->line(json_encode(['database' => DB::connection()->getDatabaseName(), 'invoice_id' => 8150,
                    'applied' => (bool) $this->option('apply'), 'already_correct' => $alreadyCorrect,
                    'before' => $before, 'after' => $after, 'backup' => $backup], JSON_UNESCAPED_UNICODE));
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
