<?php

namespace App\Services;

use App\Models\ingaz\Invoice;
use App\Services\ingaz\InvoiceCalculationService;
use App\Services\ingaz\LegacyInvoiceConstraintLinker;
use App\Support\ConstraintReferenceGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\Constraint;

class SuperAdminInvoiceCorrectionService
{
    public const LINES = [
        'direct_recruitment' => ['label' => 'تكاليف الاستقدام المباشر', 'cost' => 'direct_recruitment_cost', 'count' => 'direct_recruitment_count'],
        'external_recruitment' => ['label' => 'تكاليف الاستقدام الخارجية', 'cost' => 'external_recruitment_cost', 'count' => 'external_recruitment_count'],
        'government' => ['label' => 'الرسوم الحكومية', 'cost' => 'government_cost', 'count' => 'government_cost_count'],
        'internal_ticket' => ['label' => 'التذكرة الداخلية', 'cost' => 'internal_ticket_cost', 'count' => 'internal_ticket_count'],
    ];

    public function invoice(int $id): Invoice
    {
        return Invoice::withoutGlobalScopes()->findOrFail($id);
    }

    public function preview(int $id, array $input): array
    {
        return DB::transaction(fn () => $this->plan($id, $input));
    }

    public function apply(int $id, array $input, string $token): void
    {
        DB::transaction(function () use ($id, $input, $token) {
            $plan = $this->plan($id, $input);
            $this->require(hash_equals($plan['token'], $token), 'تغيرت الفاتورة أو القيود بعد المعاينة. أعد المعاينة قبل الحفظ.');
            // Deliberately scoped to this correction: do not disable auditing globally.
            // Keep model validations (including financial period checks) while suppressing activity logging.
            foreach ($plan['journals'] as $journal) {
                if (!$journal['changed']) continue;
                $model = Constraint::withoutGlobalScopes()->findOrFail($journal['before']['id']);
                $model->disableLogging();
                $this->require($model->forceFill($journal['updates'])->save(), 'تعذر حفظ القيد؛ تم التراجع عن العملية.');
                foreach ($journal['rows'] as $row) {
                    DB::table('fn_constraint_details')->where('id', $row['before']['id'])->update($row['updates']);
                }
            }
            DB::table('invoices')->where('id', $id)->update($plan['updates']);
            if ($plan['sale']) {
                DB::table('fn_sales')->where('id', $plan['sale']['before']['id'])->update($plan['sale']['updates']);
                if ($plan['sale']['item_updates']) DB::table('fn_sale_items')->where('id', $plan['sale']['item']['id'])->update($plan['sale']['item_updates']);
            }
        });
    }

    private function plan(int $id, array $input): array
    {
        $invoice = Invoice::withoutGlobalScopes()->whereKey($id)->lockForUpdate()->firstOrFail();
        $before = $invoice->getRawOriginal();
        $this->require(!$invoice->sponsorship_transfer_id && !$invoice->line_items, 'هذه الصفحة مخصصة لفواتير الاستقدام والتفويض ذات البنود القياسية.');
        $this->require(!$invoice->legacy_zatca_shared, 'الفاتورة معلّمة كمشاركة سابقًا مع ZATCA؛ لا يمكن تصحيح نسختها المحلية فقط.');
        $creditNotes = DB::table('recruitment_invoice_credit_notes')->where('invoice_id', $id)->lockForUpdate()->get();
        $this->require($creditNotes->isEmpty(), 'الفاتورة مرتبطة بإشعار دائن؛ لا يمكن تغيير أصل حساباته من هذه الصفحة.');

        $updates = [];
        foreach (self::LINES as $line) {
            foreach (['cost', 'count'] as $kind) {
                $field = $line[$kind];
                $value = $kind === 'count' ? (int) $input[$field] : round((float) $input[$field], 2);
                if ((float) ($before[$field] ?? 0) !== (float) $value) $updates[$field] = $value;
            }
        }
        if ((float) $invoice->discount !== (float) $input['discount']) $updates['discount'] = round((float) $input['discount'], 2);
        $moneyChanged = $updates !== [];
        foreach (['visa_recruited_count', 'insured_count'] as $field) {
            if (isset($input[$field]) && (int) $input[$field] !== (int) $invoice->$field) $updates[$field] = (int) $input[$field];
        }
        $after = array_replace($before, $updates);
        $calculator = app(InvoiceCalculationService::class);
        if ($moneyChanged) {
            $oldLines = $calculator->calculateLineTotals($before);
            $this->require($this->cents(array_sum($oldLines)) === $this->cents($invoice->subtotal), 'توزيع بنود الفاتورة القديمة لا يطابق المجموع المخزن. يلزم تصحيح توزيعها أولًا.');
            $this->require($this->cents($invoice->subtotal + $invoice->vat_amount - $invoice->discount) === $this->cents($invoice->total_due), 'إجماليات الفاتورة الأصلية غير متسقة.');
            $totals = $calculator->calculateTotals($after);
            $this->require(max($totals['subtotal'], $totals['vat_amount'], $totals['total_due']) <= 99999999.99, 'إجمالي المبالغ يتجاوز الحد المسموح لتخزين الفاتورة.');
            $this->require($totals['discount'] <= $totals['subtotal'] && $totals['total_due'] > 0, 'الخصم يجب ألا يتجاوز مجموع البنود، والإجمالي يجب أن يكون أكبر من صفر.');
            foreach (['subtotal', 'discount', 'vat_amount', 'total_due'] as $key) $updates[$key] = $totals[$key];
            $after = array_replace($before, $updates);
        }
        $newDate = $input['invoice_date'];
        $oldDate = substr($before['created_at'], 0, 10);
        $dateChanged = $oldDate !== $newDate;
        if ($dateChanged) $updates['created_at'] = $newDate . ' ' . substr($before['created_at'], 11, 8);
        $this->require($updates !== [], 'لم يتم تغيير أي قيمة.');

        $primaryId = (int) $invoice->invoice_constraint_id;
        if (!$primaryId) {
            $legacy = app(LegacyInvoiceConstraintLinker::class)->inspect($invoice);
            if (in_array($legacy['status'], ['ready', 'linked'], true)) $primaryId = (int) $legacy['constraint_id'];
            if (!$primaryId && $invoice->ingaz_delegation_id) {
                $matches = DB::table('fn_constraints')->whereNull('deleted_at')->where('statement', 'like', "قيد فاتورة تفويض إنجاز #{$invoice->ingaz_delegation_id} / الفاتورة #{$id} /%")
                    ->lockForUpdate()->pluck('id');
                if ($matches->count() === 1) $primaryId = (int) $matches->first();
            }
            $this->require($primaryId > 0, 'لا يوجد قيد فاتورة مرتبط يمكن التحقق منه. اربط القيد الصحيح أولًا.');
            $updates['invoice_constraint_id'] = $primaryId;
        }
        $roles = [
            'invoice_constraint_id' => 'قيد الفاتورة',
            'arrival_revenue_constraint_id' => 'قيد إثبات إيراد الوصول',
            'external_office_cost_constraint_id' => 'قيد تكلفة المكتب الخارجي (تكلفة فعلية مستقلة)',
            'external_office_cost_reversal_constraint_id' => 'قيد عكس تكلفة المكتب الخارجي',
            'ingaz_delegation_cost_constraint_id' => 'قيد تكلفة التفويض (تكلفة فعلية مستقلة)',
        ];
        $ids = [$primaryId];
        foreach (array_keys($roles) as $role) if ($role !== 'invoice_constraint_id' && $invoice->$role) $ids[] = (int) $invoice->$role;
        $this->require(count($ids) === count(array_unique($ids)), 'يوجد قيد مستخدم لأكثر من غرض في الفاتورة؛ لا يمكن تصحيحه تلقائيًا.');
        $headers = DB::table('fn_constraints')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $details = DB::table('fn_constraint_details')->whereIn('constraint_id', $ids)->whereNull('deleted_at')->orderBy('id')->lockForUpdate()->get()->groupBy('constraint_id');
        $journals = [];
        foreach ($roles as $role => $label) {
            $journalId = $role === 'invoice_constraint_id' ? $primaryId : (int) $invoice->$role;
            if (!$journalId) continue;
            $header = $headers->get($journalId);
            $this->require($header && !$header->deleted_at, 'أحد القيود المرتبطة مفقود أو محذوف.');
            $rows = $details->get($journalId, collect())->map(fn ($row) => (array) $row)->all();
            $this->assertBalanced($header, $rows);
            $journalUpdates = [];
            $mapped = [];
            $isPrimary = $role === 'invoice_constraint_id';
            $isRevenue = $role === 'arrival_revenue_constraint_id';
            $alreadyMatchesTarget = $isPrimary && $moneyChanged
                && $this->cents($header->value) !== $this->cents($invoice->total_due)
                && $this->cents($header->value) === $this->cents($after['total_due']);
            if ($isPrimary) {
                $this->require($this->cents($header->value) === $this->cents($invoice->total_due) || $alreadyMatchesTarget, 'قيمة قيد الفاتورة لا تطابق إجمالي الفاتورة الأصلي أو المبلغ الجديد المطلوب.');
                $this->require(substr($header->date, 0, 10) === $oldDate, 'تاريخ قيد الفاتورة لا يطابق تاريخها الأصلي؛ يلزم مراجعة الربط.');
            }
            if ($moneyChanged && ($isPrimary || $isRevenue)) {
                $oldParts = $this->components($before, $isPrimary);
                $newParts = $this->components($after, $isPrimary);
                // A previous partial correction may have updated only the journal.
                // Validate its complete target distribution before retaining its amounts.
                if ($alreadyMatchesTarget) $oldParts = $newParts;
                $this->require($this->cents($header->value) === array_sum($oldParts), 'قيمة قيد الإيراد لا تطابق توزيع الفاتورة.');
                $mapped = app(InvoiceJournalAmountMapper::class)->map($rows, $oldParts, $newParts);
                if ($alreadyMatchesTarget) {
                    $mapped = [];
                } else {
                    $journalUpdates['value'] = array_sum($newParts) / 100;
                }
            }
            if ($dateChanged && $isPrimary) {
                $journalUpdates['date'] = $newDate;
                $years = DB::table('fn_years')->where('from_date', '<=', $newDate)->where('to_date', '>=', $newDate)->where('status', 1)->lockForUpdate()->get();
                $this->require($years->count() === 1, 'يجب أن يقع التاريخ الجديد داخل سنة مالية نشطة واحدة.');
                $journalUpdates['year_id'] = $years->first()->id;
            }
            if ($journalUpdates || $alreadyMatchesTarget) {
                foreach (ConstraintReferenceGuard::referencesFor($journalId) as $reference) {
                    $this->require($reference['table'] === 'invoices' && $reference['id'] === $id, 'القيد مرتبط بمستند آخر؛ لا يمكن تغييره ضمن فاتورة واحدة.');
                }
                $this->assertOpenPeriod($header->date, $header->year_id);
                $this->assertOpenPeriod($journalUpdates['date'] ?? $header->date, $journalUpdates['year_id'] ?? $header->year_id);
            }
            $plannedRows = [];
            foreach ($rows as $row) {
                $rowUpdates = $mapped[$row['id']] ?? [];
                foreach (['date', 'year_id'] as $field) if (isset($journalUpdates[$field])) $rowUpdates[$field] = $journalUpdates[$field];
                $plannedRows[] = ['before' => $row, 'updates' => $rowUpdates];
            }
            $this->assertBalanced((object) array_replace((array) $header, $journalUpdates), array_map(fn ($row) => array_replace($row['before'], $row['updates']), $plannedRows));
            $journals[] = ['label' => $label, 'before' => (array) $header, 'updates' => $journalUpdates, 'rows' => $plannedRows, 'changed' => $journalUpdates !== [], 'already_matches_target' => $alreadyMatchesTarget];
        }

        // Receipts represent actual payments: invoice corrections must not invent or erase cash movements.
        $receiptQuery = DB::table('fn_bonds')->where(function ($query) use ($invoice, $id) {
            $query->where('statement', 'like', "% / الفاتورة #{$id} /%");
            if ($invoice->contract_id) $query->orWhere('recruitment_contract_id', $invoice->contract_id);
        });
        $receipts = $receiptQuery->orderBy('id')->lockForUpdate()->get();
        $settlements = DB::table('musaned_transfer_items')->where('invoice_id', $id)->lockForUpdate()->get();
        if ($moneyChanged && $this->cents($after['total_due']) !== $this->cents($before['total_due'])) {
            $this->require($settlements->isEmpty(), 'الفاتورة مرتبطة بتسوية مساند؛ يجب مراجعة التسوية قبل تغيير إجماليها.');
            if ($invoice->is_paid && $this->cents($after['total_due']) > $this->cents($before['total_due'])) $updates['is_paid'] = false;
        }
        $accounts = DB::table('fn_accounts')->whereIn('id', $details->flatten(1)->pluck('account_id')->unique())->pluck('name', 'id')->all();
        $sale = $this->pendingSale($invoice, array_replace($before, $updates), $moneyChanged);
        $state = [$before, $headers->values()->all(), $details->all(), $receipts->all(), $settlements->all(), $input, $updates, $journals, $sale];
        return [
            'invoice' => $invoice, 'before' => $before, 'after' => array_replace($before, $updates),
            'updates' => $updates, 'journals' => $journals, 'accounts' => $accounts, 'receipts' => $receipts,
            'sale' => $sale, 'input' => $input, 'token' => hash_hmac('sha256', json_encode($state, JSON_THROW_ON_ERROR), config('app.key')),
        ];
    }

    private function pendingSale(Invoice $invoice, array $after, bool $moneyChanged): ?array
    {
        // send_to_zatca is an intent to send, not proof of a submitted electronic invoice.
        if (!$invoice->finance_sale_id) return null;
        $sale = DB::table('fn_sales')->where('id', $invoice->finance_sale_id)->lockForUpdate()->first();
        $this->require($sale !== null, 'نسخة المبيعات المرتبطة مفقودة؛ يجب مراجعة الربط قبل التصحيح.');
        $this->require(!$sale->zatca_invoice_id && !DB::table('zatca_invoices')->where('sale_id', $sale->id)->lockForUpdate()->exists(), 'نسخة المبيعات لها مستند ZATCA؛ لا يمكن تعديل النسخة المحلية من هذه الصفحة.');
        $this->require(!$sale->is_confirmed && !$sale->constraint_id, 'نسخة المبيعات مرتبطة بترحيل محاسبي مستقل؛ يلزم مراجعته قبل التصحيح.');
        $this->require(!DB::table('invoices')->where('finance_sale_id', $sale->id)->where('id', '!=', $invoice->id)->exists(), 'نسخة المبيعات مشتركة مع فاتورة أخرى.');
        $this->require($sale->date === $invoice->created_at->format('Y-m-d') && $this->cents($sale->final_total) === $this->cents($invoice->total_due), 'تاريخ أو إجمالي نسخة المبيعات لا يطابق الفاتورة الأصلية؛ يلزم مراجعة الربط.');
        $items = DB::table('fn_sale_items')->where('sale_id', $sale->id)->orderBy('id')->lockForUpdate()->get();
        $updates = ['date' => substr($after['created_at'], 0, 10)];
        $itemUpdates = [];
        if ($moneyChanged) {
            $this->require($items->count() === 1, 'نسخة المبيعات تحتوي توزيع بنود مختلفًا؛ يلزم مراجعته قبل تعديل المبالغ.');
            $item = $items->first();
            $this->require((float) $item->amount === 1.0 && !$item->stock_id && $this->cents($item->final_total) === $this->cents($invoice->total_due), 'بند نسخة المبيعات لا يطابق البند الإجمالي للفاتورة.');
            $net = round($after['subtotal'] - $after['discount'], 2);
            $updates += ['total_item' => $net, 'discount' => 0, 'total_tax_value' => $after['vat_amount'], 'final_total' => $after['total_due']];
            $itemUpdates = ['price' => $net, 'total_price_before' => $net, 'discount_per' => 0, 'discount_value' => 0,
                'tax_per' => $net > 0 && $after['vat_amount'] > 0 ? InvoiceCalculationService::VAT_RATE * 100 : 0,
                'tax_value' => $after['vat_amount'], 'final_total' => $after['total_due']];
        }
        return ['before' => (array) $sale, 'items' => $items->all(), 'item' => (array) $items->first(), 'updates' => $updates, 'item_updates' => $itemUpdates];
    }

    private function components(array $invoice, bool $includeVat): array
    {
        $gross = app(InvoiceCalculationService::class)->calculateLineTotals($invoice);
        $gross = array_map(fn ($value) => $this->cents($value), $gross);
        $sum = array_sum($gross);
        $net = $this->cents($invoice['total_due']) - $this->cents($invoice['vat_amount']);
        $parts = [];
        $remaining = $net;
        $positive = array_keys(array_filter($gross));
        foreach ($gross as $key => $amount) {
            $value = $amount ? ($key === end($positive) ? $remaining : (int) round($net * $amount / max(1, $sum))) : 0;
            $parts[$key] = $value;
            $remaining -= $value;
        }
        $this->require($remaining === 0, 'تعذر توزيع صافي الفاتورة على البنود.');
        if ($includeVat) $parts['vat'] = $this->cents($invoice['vat_amount']);
        return $parts;
    }

    private function assertBalanced(object $header, array $rows): void
    {
        $debit = $credit = 0;
        foreach ($rows as $row) {
            $d = $this->cents($row['debtor_value']);
            $c = $this->cents($row['creditor_value']);
            $this->require($d >= 0 && $c >= 0 && !($d && $c), 'القيد يحتوي سطرًا غير صالح.');
            $debit += $d;
            $credit += $c;
        }
        $this->require($debit > 0 && $debit === $credit && $debit === $this->cents($header->value), 'القيد الأصلي غير متوازن أو قيمته لا تطابق أسطره.');
    }

    private function assertOpenPeriod(string $date, ?int $yearId): void
    {
        $year = DB::table('fn_years')->where('id', $yearId)->lockForUpdate()->first();
        $this->require($year && $date >= $year->from_date && $date <= $year->to_date, 'تاريخ القيد خارج سنته المالية.');
        $closed = DB::table('fn_fiscal_year_closures')->whereNull('reversed_at')->whereBetween('closing_date', [$year->from_date, $year->to_date])->where('closing_date', '>=', $date)->lockForUpdate()->exists();
        $this->require(!$closed, 'الفترة المالية مقفلة؛ يلزم فتحها من دورة الإقفال قبل التصحيح.');
    }

    private function cents($amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function require(bool $condition, string $message): void
    {
        if (!$condition) throw ValidationException::withMessages(['invoice' => $message]);
    }
}
