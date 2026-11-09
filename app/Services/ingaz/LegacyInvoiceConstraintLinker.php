<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Invoice;
use App\Support\RecruitmentRevenueRecognition;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;

class LegacyInvoiceConstraintLinker
{
    public function inspect(Invoice $invoice, ?\Illuminate\Support\Collection $candidates = null): array
    {
        if (!$invoice->legacy_invoice_number && !$invoice->contract_id) {
            return ['status' => 'unsupported_invoice'];
        }
        if ($invoice->contract_id && !$invoice->isRecruitmentRevenue()) {
            return ['status' => 'not_recruitment_revenue'];
        }
        $lookupCandidates = $candidates === null;
        $candidates ??= DB::table('fn_constraints')->whereNull('deleted_at')
            ->where(function ($query) use ($invoice) {
                if (!$invoice->legacy_invoice_number) {
                    // Match both identifiers with delimiters, never the amount alone.
                    $query->where('statement', 'like', 'قيد فاتورة طلب استقدام للعقد #'
                        . (int) $invoice->contract_id . ' / الفاتورة #' . (int) $invoice->id . ' / مساند %');
                    return;
                }
                $query->where('legacy_invoice_number', $invoice->legacy_invoice_number)
                    ->orWhere('statement', 'فاتورة: ' . $invoice->legacy_invoice_number)
                    ->orWhere('statement', 'فاتورة رقم: ' . $invoice->legacy_invoice_number);
            })->get();
        if ($lookupCandidates && $candidates->isEmpty() && !$invoice->legacy_invoice_number) {
            $candidates = $this->reissuedInvoiceCandidates($invoice);
        }
        if ($candidates->count() !== 1) {
            return $candidates->isEmpty() ? ['status' => 'missing']
                : ['status' => 'ambiguous', 'candidate_ids' => $candidates->pluck('id')->map(fn ($id) => (int) $id)->all()];
        }
        $source = $candidates->first();
        $base = ['constraint_id' => $source->id];
        if ($source->legacy_invoice_number && (int) $source->legacy_invoice_number !== (int) $invoice->legacy_invoice_number) {
            return $base + ['status' => 'identity_mismatch'];
        }
        if (DB::table('invoices')->where('invoice_constraint_id', $source->id)->where('id', '!=', $invoice->id)->exists()) {
            return $base + ['status' => 'source_already_linked'];
        }

        $headerDifferences = [];
        if (!$this->branchesMatch($source->branch_id, $invoice->branch_id)) {
            $headerDifferences['branch_id'] = ['invoice' => $invoice->branch_id, 'constraint' => $source->branch_id];
        }
        // Imported invoices can have a different issue date from the original
        // journal posting date. Preserve both dates; they do not establish identity.
        if (abs((float) $source->value - (float) $invoice->total_due) > 0.009) {
            $headerDifferences['amount'] = ['invoice' => $invoice->total_due, 'constraint' => $source->value];
        }
        if ($headerDifferences) {
            return $base + ['status' => 'header_mismatch', 'differences' => $headerDifferences];
        }
        $lines = DB::table('fn_constraint_details')->where('constraint_id', $source->id)->whereNull('deleted_at')->get();
        $clientAccount = $invoice->contract?->user?->account_id ?: $invoice->client?->account_id;
        $reason = match (true) {
            !$clientAccount => 'no_client_account',
            $lines->isEmpty() => 'no_lines',
            $lines->contains(fn ($line) => !$this->branchesMatch($line->branch_id, $invoice->branch_id)) => 'line_branch',
            abs((float) $lines->sum('debtor_value') - (float) $invoice->total_due) > 0.009
                || abs((float) $lines->sum('creditor_value') - (float) $invoice->total_due) > 0.009 => 'line_totals',
            abs((float) $lines->where('account_id', $clientAccount)->sum('debtor_value') - (float) $invoice->total_due) > 0.009
                || (float) $lines->where('account_id', $clientAccount)->sum('creditor_value') !== 0.0 => 'client_account',
            default => null,
        };
        if ($reason) {
            return $base + ['status' => 'lines_mismatch', 'reason' => $reason, 'client_account_id' => $clientAccount ? (int) $clientAccount : null,
                'debit_account_ids' => $lines->where('debtor_value', '>', 0)->pluck('account_id')->map(fn ($id) => (int) $id)->unique()->values()->all()];
        }
        // The source accounts, not the current company setting, determine recognition.
        $income = false;
        $liability = false;
        foreach ($lines->where('creditor_value', '>', 0) as $line) {
            $accountId = $line->account_id;
            $seen = [];
            while ($accountId && !isset($seen[$accountId])) {
                $seen[$accountId] = true;
                $account = DB::table('fn_accounts')->where('id', $accountId)->first(['name', 'parent_id']);
                if (!$account) {
                    return $base + ['status' => 'account_missing'];
                }
                $income = $income || in_array($account->name, ['الإيرادات', 'الايرادات'], true);
                $liability = $liability || in_array($account->name, ['التزامات عقود', 'التزامات عقود الاستقدام'], true);
                $accountId = $account->parent_id;
            }
        }
        $mode = $income && !$liability ? RecruitmentRevenueRecognition::MODE_ON_INVOICE
            : ($liability && !$income ? RecruitmentRevenueRecognition::MODE_ON_ARRIVAL : null);
        if ($invoice->contract_id && !$mode) {
            return $base + ['status' => 'recognition_ambiguous'];
        }
        return $base + [
            'status' => $invoice->invoice_constraint_id && (int) $invoice->invoice_constraint_id !== (int) $source->id
                ? 'link_conflict' : ((int) $invoice->invoice_constraint_id === (int) $source->id ? 'linked' : 'ready'),
            'mode' => $mode,
        ];
    }

    /**
     * The native journal statement embeds the invoice id it was posted for. When the
     * invoice was later re-issued under a new id, the journal keeps the old one.
     * Accept such a journal only when this is the contract's sole invoice and the old
     * id no longer exists; inspect() still verifies amount, customer and lines.
     */
    private function reissuedInvoiceCandidates(Invoice $invoice): \Illuminate\Support\Collection
    {
        $contractId = (int) $invoice->contract_id;
        if (DB::table('invoices')->where('contract_id', $contractId)->where('id', '!=', $invoice->id)->exists()) {
            return collect();
        }
        $prefix = 'قيد فاتورة طلب استقدام للعقد #' . $contractId . ' / الفاتورة #';

        return DB::table('fn_constraints')->whereNull('deleted_at')
            ->where('statement', 'like', $prefix . '%')
            ->get()
            ->filter(function ($constraint) use ($prefix) {
                if (!preg_match('/^(\d+) \/ /u', mb_substr((string) $constraint->statement, mb_strlen($prefix)), $match)) {
                    return false;
                }
                return !DB::table('invoices')->where('id', (int) $match[1])->exists();
            })
            ->values();
    }

    private function branchesMatch(?int $sourceBranchId, ?int $invoiceBranchId): bool
    {
        if ($sourceBranchId === $invoiceBranchId) {
            return true;
        }
        // BranchScoped treats unassigned documents as belonging only to the main branch.
        // Keep historical branch fields intact; never match an explicit different branch.
        if ($sourceBranchId !== null && $invoiceBranchId !== null) {
            return false;
        }
        $mainBranchId = app(\App\Services\BranchAccessService::class)->mainBranchId();
        return $mainBranchId !== null && ($sourceBranchId ?? $invoiceBranchId) === $mainBranchId;
    }

    public function link(Invoice $invoice): Constraint
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);
            return $this->applyLink($invoice, $this->inspect($invoice));
        });
    }

    /**
     * Manual alternative when automatic matching fails: an accountant names the
     * source journal (by id or code). It must still pass every inspect() check.
     */
    public function linkToSource(Invoice $invoice, int $sourceIdOrCode): Constraint
    {
        return DB::transaction(function () use ($invoice, $sourceIdOrCode) {
            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);
            $rows = DB::table('fn_constraints')->whereNull('deleted_at')
                ->where(fn ($q) => $q->where('id', $sourceIdOrCode)->orWhere('code', $sourceIdOrCode))->get();
            if ($rows->count() !== 1) {
                throw \Illuminate\Validation\ValidationException::withMessages(['source' => $rows->isEmpty()
                    ? 'لا يوجد قيد قائم بالرقم ' . $sourceIdOrCode . '.'
                    : 'الرقم ' . $sourceIdOrCode . ' يطابق أكثر من قيد (رقم داخلي ورقم قيد)؛ استخدم الرقم الداخلي.']);
            }
            return $this->applyLink($invoice, $this->inspect($invoice, $rows));
        });
    }

    private function applyLink(Invoice $invoice, array $result): Constraint
    {
        if (!in_array($result['status'], ['ready', 'linked'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['credit_note' => $this->describe($invoice, $result)]);
        }
        $source = Constraint::withoutGlobalScopes()->whereNull('deleted_at')->lockForUpdate()->findOrFail($result['constraint_id']);
        $updates = ['invoice_constraint_id' => $source->id];
        if ($invoice->contract_id && $result['mode']) {
            $updates['revenue_recognition_mode'] = $result['mode'];
        }
        $invoice->fill($updates);
        if ($invoice->isDirty()) {
            $invoice->save();
        }
        return $source;
    }

    /** Arabic explanation of an inspect() result: what is wrong, where, and how to fix it. */
    public function describe(Invoice $invoice, array $result): string
    {
        $journal = fn ($id) => ($row = DB::table('fn_constraints')->where('id', $id)->first(['id', 'code']))
            ? 'القيد رقم ' . ($row->code ?: $row->id) . ' (المعرّف ' . $row->id . ')' : 'القيد #' . $id;
        $account = fn ($id) => ($row = DB::table('fn_accounts')->where('id', $id)->first(['code', 'name']))
            ? trim($row->name) . ' (' . $row->code . ')' : 'حساب #' . $id;
        $money = fn ($v) => number_format((float) $v, 2);
        $source = isset($result['constraint_id']) ? $journal($result['constraint_id']) : null;

        [$cause, $fix] = match ($result['status']) {
            'missing' => ['لم يُعثر على قيد الفاتورة الأصلي. النظام يبحث عن قيد نصّه يذكر رقم العقد ورقم الفاتورة معاً'
                . ($invoice->legacy_invoice_number ? ' (أو رقم الفاتورة القديم ' . $invoice->legacy_invoice_number . ')' : '') . '.',
                'ابحث في القيود عن قيد إثبات هذه الفاتورة؛ وإن لم يكن مسجلاً فيجب تسجيله أولاً.'],
            'ambiguous' => ['يوجد أكثر من قيد يطابق هذه الفاتورة: ' . implode('، ', array_map($journal, $result['candidate_ids'] ?? [])) . '. غالباً قيد مكرر.',
                'اعكس القيد المكرر واترك قيداً واحداً، أو اربط القيد الصحيح يدوياً.'],
            'identity_mismatch' => [$source . ' يحمل رقم فاتورة قديم مختلف عن رقم هذه الفاتورة.', 'تأكد أن القيد يخص هذه الفاتورة فعلاً.'],
            'source_already_linked' => [$source . ' مربوط بفاتورة أخرى رقم #'
                . DB::table('invoices')->where('invoice_constraint_id', $result['constraint_id'])->where('id', '!=', $invoice->id)->value('id') . '.',
                'راجع أي الفاتورتين يخصها القيد؛ لا يجوز ربط قيد واحد بفاتورتين.'],
            'header_mismatch' => [$source . ' لا يطابق رأس الفاتورة: ' . implode('؛ ', array_map(function ($field, $v) use ($money) {
                    return $field === 'amount'
                        ? 'المبلغ في الفاتورة ' . $money($v['invoice']) . ' وفي القيد ' . $money($v['constraint'])
                        : 'الفرع في الفاتورة ' . ($v['invoice'] ?? 'غير محدد') . ' وفي القيد ' . ($v['constraint'] ?? 'غير محدد');
                }, array_keys($result['differences'] ?? []), $result['differences'] ?? [])) . '.',
                'صحّح الفاتورة أو القيد حتى يتطابق المبلغ والفرع.'],
            'lines_mismatch' => match ($result['reason'] ?? null) {
                'no_client_account' => ['عميل العقد ليس له حساب محاسبي.', 'أنشئ حساب العميل من بيانات العميل ثم أعد المحاولة.'],
                'no_lines' => [$source . ' بلا تفاصيل.', 'راجع القيد في المالية.'],
                'line_branch' => ['بعض سطور ' . $source . ' مسجلة على فرع غير فرع الفاتورة.', 'صحّح فرع سطور القيد.'],
                'line_totals' => ['مجموع مدين/دائن ' . $source . ' لا يساوي إجمالي الفاتورة ' . $money($invoice->total_due) . '.', 'صحّح مبالغ سطور القيد.'],
                'client_account' => [$source . ' مسجّل على حساب ' . implode(' / ', array_map($account, $result['debit_account_ids'] ?? []))
                    . '، بينما حساب عميل العقد الحالي هو ' . $account($result['client_account_id']) . '. غالباً حساب عميل مكرر أو تغيّر العميل بعد إصدار الفاتورة.',
                    'ادمج حسابي العميل أو انقل السطر إلى الحساب الصحيح بقيد تسوية، ثم أعد المحاولة.'],
                default => ['سطور ' . $source . ' لا تطابق الفاتورة.', 'راجع سطور القيد.'],
            },
            'account_missing' => ['أحد حسابات ' . $source . ' محذوف من شجرة الحسابات.', 'أعد الحساب أو صحّح سطر القيد.'],
            'recognition_ambiguous' => [$source . ' لا يوضح هل سُجل المبلغ إيراداً أم التزامات عقود.', 'راجع الحسابات الدائنة في القيد.'],
            'link_conflict' => ['الفاتورة مربوطة أصلاً بقيد آخر غير ' . $source . '.', 'راجع الربط الحالي للفاتورة قبل تغييره.'],
            'not_recruitment_revenue' => ['هذه الفاتورة ليست فاتورة إيراد استقدام (فاتورة إغلاق أو تكلفة).', 'أنشئ الإشعار على فاتورة الإيراد الصحيحة للعقد.'],
            default => ['حالة غير مدعومة (' . $result['status'] . ').', 'راجع الفاتورة.'],
        };

        $message = 'تعذر إنشاء إشعار دائن للفاتورة #' . ($invoice->legacy_invoice_number ?: $invoice->id)
            . ($invoice->contract_id ? ' للعقد #' . $invoice->contract_id : '') . ' بمبلغ ' . $money($invoice->total_due) . ' ريال.'
            . ' السبب: ' . $cause . ' الحل: ' . $fix;
        if (in_array($result['status'], ['missing', 'ambiguous'], true) && ($hints = $this->possibleSources($invoice))) {
            $message .= ' قيود محتملة بنفس المبلغ للعقد: ' . implode('، ', $hints) . '.';
        }
        return $message . ' حل بديل: إذا تحقق المحاسب من القيد الصحيح يربطه مسؤول النظام يدوياً (الأمر finance:repair-recruitment-invoice-links --mode=manual --invoice='
            . $invoice->id . ' --source=رقم_القيد)، ويُرفض الربط إن لم يطابق القيد الفاتورة.'
            . ' [رمز الحالة: ' . $result['status'] . (isset($result['reason']) ? '/' . $result['reason'] : '') . ']';
    }

    /** Journals for the same contract and amount, shown as hints only — never linked automatically. */
    private function possibleSources(Invoice $invoice): array
    {
        if (!$invoice->contract_id) {
            return [];
        }
        return DB::table('fn_constraints')->whereNull('deleted_at')
            ->whereRaw('ABS(value - ?) < 0.01', [(float) $invoice->total_due])
            ->where('statement', 'regexp', '#' . (int) $invoice->contract_id . '([^0-9]|$)')
            ->orderBy('id')->limit(5)->get(['id', 'code', 'statement'])
            ->map(fn ($row) => 'رقم ' . ($row->code ?: $row->id) . ' «' . mb_substr($row->statement, 0, 80) . '»')
            ->all();
    }
}
