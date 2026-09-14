<?php

namespace App\Console\Commands;

use App\Models\ingaz\Invoice;
use App\Services\ingaz\LegacyInvoiceConstraintLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairAlrafedainInvoiceBreakdowns extends Command
{
    protected $signature = 'invoices:repair-alrafedain-breakdowns {--apply} {--expected-database=}';
    protected $description = 'تصحيح تفصيل فواتير الاستقدام المنقولة من القيود الأصلية المؤكدة مع حفظ القيم السابقة';

    /** Recognize the imported direct/external/government/tax posting pattern, without guessing missing lines. */
    public static function plan(array $invoice, array $credits): array
    {
        $money = static fn ($value) => (int) round((float) $value * 100);
        $vat = $money($invoice['vat_amount'] ?? 0);
        $net = $money($invoice['subtotal'] ?? 0);
        if ($vat <= 0 || $net <= 0 || $money($invoice['total_due'] ?? 0) !== $net + $vat
            || $money($invoice['discount'] ?? 0) !== 0 || $money($invoice['internal_ticket_cost'] ?? 0) !== 0
            || !empty($invoice['line_items'])) {
            return ['status' => 'unsupported_invoice', 'reason' => 'فاتورة بلا ضريبة مؤكدة أو بها خصم أو تذكرة أو بنود مخصصة أو إجمالي غير مطابق'];
        }
        $sales = [];
        $government = 0;
        $tax = 0;
        $total = 0;
        foreach ($credits as $line) {
            $amount = $money($line['creditor_value']);
            if ($amount <= 0) {
                return ['status' => 'unsupported_posting'];
            }
            $total += $amount;
            $name = (string) ($line['account_name'] ?? '');
            if (preg_match('/ضريب|قيمة مضافة/u', $name)) {
                $tax += $amount;
            } elseif (str_contains($name, 'حكوم')) {
                $government += $amount;
            } elseif (preg_match('/مبيعات|إيراد|ايراد/u', $name)) {
                $sales[] = ['account_id' => (int) $line['account_id'], 'amount' => $amount];
            } else {
                return ['status' => 'unsupported_account', 'reason' => 'حساب دائن لا يحدد دور البند بوضوح'];
            }
        }
        if ($tax !== $vat || $total !== $net + $vat || count($sales) < 1 || count($sales) > 2
            || (count($sales) === 2 && $sales[0]['account_id'] !== $sales[1]['account_id'])) {
            return ['status' => 'unsupported_posting', 'reason' => 'توزيع القيد أو حساباته لا يطابق نمط الفواتير المنقولة'];
        }
        // The first sales posting is direct, and its own 15% VAT must exactly match the stored tax.
        $direct = $sales[0]['amount'];
        $external = $sales[1]['amount'] ?? 0;
        if ((int) round($direct * 0.15) !== $vat || $direct + $external + $government !== $net) {
            return ['status' => 'tax_base_mismatch', 'reason' => 'لم تتأكد تكلفة المباشر من مبلغ الضريبة وترتيب القيد الأصلي'];
        }
        $after = ['direct_recruitment_cost' => $direct / 100, 'direct_recruitment_count' => 1,
            'external_recruitment_cost' => $external / 100, 'external_recruitment_count' => $external > 0 ? 1 : 0,
            'government_cost' => $government / 100, 'government_cost_count' => $government > 0 ? 1 : 0];
        $same = true;
        foreach ($after as $key => $value) {
            $same = $same && (float) ($invoice[$key] ?? 0) === (float) $value;
        }
        if ($same) {
            return ['status' => 'already_correct', 'after' => $after];
        }
        if ($money($invoice['direct_recruitment_cost'] ?? 0) !== $net
            || (int) ($invoice['direct_recruitment_count'] ?? 0) !== 1
            || $money($invoice['external_recruitment_cost'] ?? 0) !== 0
            || $money($invoice['government_cost'] ?? 0) !== 0) {
            return ['status' => 'existing_breakdown_differs', 'reason' => 'التفصيل الحالي ليس التجميع الأصلي للاستيراد؛ يترك للمراجعة'];
        }
        return ['status' => 'ready', 'after' => $after];
    }

    public function handle(LegacyInvoiceConstraintLinker $linker): int
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== 'alrafedain_dashboard' || $this->option('expected-database') !== $database) {
            $this->error('الأمر مخصص لقاعدة alrafedain_dashboard ويجب تحديدها في --expected-database.');
            return self::FAILURE;
        }
        $directory = storage_path('app/legacy-invoice-breakdowns');
        if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
            $this->error('تعذر إنشاء مجلد التقارير.');
            return self::FAILURE;
        }
        $path = $directory . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.jsonl';
        $file = fopen($path, 'x');
        if (!$file) {
            $this->error('تعذر إنشاء التقرير.');
            return self::FAILURE;
        }
        $counts = [];
        try {
            Invoice::withoutGlobalScopes()->whereNotNull('legacy_invoice_number')->orderBy('id')
                ->chunkById(100, function ($invoices) use ($linker, $file, &$counts, $database) {
                    foreach ($invoices as $row) {
                        $status = DB::transaction(function () use ($row, $linker, $file, $database) {
                            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($row->id);
                            $source = null;
                            $credits = [];
                            if (!$invoice->contract_id || !$invoice->legacy_source_payload) {
                                $plan = ['status' => 'not_imported_recruitment'];
                            } elseif (!$invoice->invoice_constraint_id) {
                                $plan = ['status' => 'unlinked', 'reason' => 'يجب تأكيد وربط القيد الأصلي أولاً'];
                            } else {
                                $source = DB::table('fn_constraints')->where('id', $invoice->invoice_constraint_id)
                                    ->whereNull('deleted_at')->lockForUpdate()->first();
                                DB::table('fn_constraint_details')->where('constraint_id', $invoice->invoice_constraint_id)
                                    ->whereNull('deleted_at')->lockForUpdate()->get();
                                $link = $linker->inspect($invoice);
                                if (!$source || $link['status'] !== 'linked' || ($link['mode'] ?? null) !== 'on_invoice') {
                                    $plan = ['status' => 'source_not_confirmed', 'link_result' => $link];
                                } else {
                                    $credits = DB::table('fn_constraint_details as d')->join('fn_accounts as a', 'a.id', '=', 'd.account_id')
                                        ->where('d.constraint_id', $source->id)->whereNull('d.deleted_at')->where('d.creditor_value', '>', 0)
                                        ->orderBy('d.id')->get(['d.id', 'd.account_id', 'd.creditor_value', 'a.name as account_name'])
                                        ->map(fn ($line) => (array) $line)->all();
                                    $plan = self::plan($invoice->getAttributes(), $credits);
                                }
                            }
                            $record = ['database' => $database, 'invoice_id' => $invoice->id,
                                'legacy_invoice_number' => $invoice->legacy_invoice_number,
                                'constraint_id' => $invoice->invoice_constraint_id, 'result' => $plan,
                                'before' => $invoice->only(['direct_recruitment_cost', 'direct_recruitment_count',
                                    'external_recruitment_cost', 'external_recruitment_count', 'government_cost', 'government_cost_count',
                                    'subtotal', 'vat_amount', 'total_due', 'updated_at']), 'source_credits' => $credits,
                                'apply_requested' => (bool) $this->option('apply')];
                            if (fwrite($file, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n") === false || !fflush($file)) {
                                throw new \RuntimeException('تعذر حفظ القيم السابقة؛ أوقف التنفيذ.');
                            }
                            if ($plan['status'] === 'ready' && $this->option('apply')) {
                                $invoice->update($plan['after']);
                                return 'corrected';
                            }
                            return $plan['status'];
                        });
                        $counts[$status] = ($counts[$status] ?? 0) + 1;
                    }
                });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->line('تقرير المعاملات السابقة: ' . $path);
            return self::FAILURE;
        } finally {
            fclose($file);
        }
        $this->line(json_encode(['database' => $database, 'applied' => (bool) $this->option('apply'),
            'counts' => $counts, 'report' => $path], JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }
}
