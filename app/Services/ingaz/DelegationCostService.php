<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\ingaz\Invoice;
use App\Support\AutomaticJournalEntries;
use App\Support\IngazDelegationTimeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class DelegationCostService
{
    public function __construct(private ConstraintService $journals, private YearService $years) {}

    private function setting(string $key): mixed
    {
        return ConfirmSetting::where('title', $key)->latest('updated_at')->value('value');
    }

    public function defaultAmount(): float
    {
        return round((float) ($this->setting('ingaz_delegation_fixed_cost') ?? 137), 2);
    }

    public function paymentAccounts(): array
    {
        $leaf = Account::query()->whereDoesntHave('children')
            ->when(\App\Support\BranchSharingSettings::accountsPerBranch(),
                fn ($q) => $q->assignableTo(\App\Support\BranchQueryContext::activeBranchId()));
        $custodyIds = Schema::hasTable('hr_employee_custodies')
            ? DB::table('hr_employee_custodies')->where('status', 'active')->pluck('account_id') : [];

        $accounts = Account::query()->get(['id', 'parent_id', 'type', 'code', 'name']);
        $bankIds = self::accountFamilyIds($accounts, 'bank');
        $custodyFamilyIds = self::accountFamilyIds($accounts, 'custody');

        return [
            'bank' => (clone $leaf)->whereIn('id', $bankIds)->orderBy('code')->get(),
            'custody' => (clone $leaf)->where(function ($q) use ($custodyIds, $custodyFamilyIds) {
                $q->whereIn('id', $custodyIds)->orWhereIn('id', $custodyFamilyIds);
            })->orderBy('code')->get(),
            'ingaz' => (clone $leaf)->whereKey((int) $this->setting('ingaz_delegation_ingaz_account'))->get(),
        ];
    }

    /** Include legacy accounts and descendants even when their individual type is generic. */
    public static function accountFamilyIds(iterable $accounts, string $source): array
    {
        $accounts = collect($accounts);
        $ids = [];
        foreach ($accounts as $account) {
            $type = $account->type instanceof AccountTypesEnum ? $account->type->value : (int) $account->type;
            $matches = $source === 'bank'
                ? in_array($type, [AccountTypesEnum::Banks->value, AccountTypesEnum::Bank->value, AccountTypesEnum::BankName->value], true)
                : ($type === AccountTypesEnum::Custody->value
                    || in_array((string) $account->code, ['126', '1216'], true)
                    || preg_match('/^(?:عهدة|عهده|عهد)(?:\s|$)/u', trim((string) $account->name)));
            if ($matches) $ids[(int) $account->id] = true;
        }
        do {
            $changed = false;
            foreach ($accounts as $account) {
                if (!isset($ids[(int) $account->id]) && isset($ids[(int) $account->parent_id])) {
                    $ids[(int) $account->id] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        return array_keys($ids);
    }

    public function post(array $data): int
    {
        Validator::make($data, [
            'date' => 'required|date_format:Y-m-d', 'reference' => 'required|string|max:100',
            'confirmed' => 'accepted', 'payment_source_type' => 'required|in:bank,custody,ingaz',
            'payment_account_id' => 'required|integer',
            'delegation_ids' => 'nullable|array|max:200', 'delegation_ids.*' => 'required|integer|distinct',
            'contract_ids' => 'nullable|array|max:200', 'contract_ids.*' => 'required|integer|distinct',
            'amounts' => 'nullable|array', 'contract_amounts' => 'nullable|array',
        ])->validate();

        $delegationIds = array_values($data['delegation_ids'] ?? []);
        $contractIds = array_values($data['contract_ids'] ?? []);
        $itemCount = count($delegationIds) + count($contractIds);
        if ($itemCount < 1 || $itemCount > 200) {
            $this->fail('اختر من تفويض أو عقد استقدام واحد إلى 200 عنصر.');
        }

        foreach ($delegationIds as $id) {
            Validator::make(['amounts' => $data['amounts'] ?? []], [
                "amounts.$id" => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            ])->validate();
        }
        foreach ($contractIds as $id) {
            Validator::make(['amounts' => $data['contract_amounts'] ?? []], [
                "amounts.$id" => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            ])->validate();
        }

        return DB::transaction(function () use ($data, $delegationIds, $contractIds, $itemCount) {
            if (!AutomaticJournalEntries::enabled()) {
                $this->fail('القيود التلقائية غير مفعلة؛ فعّلها قبل تأكيد تكلفة التفويض.');
            }
            $payment = $this->paymentAccounts()[$data['payment_source_type']]->firstWhere('id', (int) $data['payment_account_id']);
            $expense = Account::whereKey((int) $this->setting('ingaz_delegation_cost_account'))
                ->whereDoesntHave('children')->first();
            if (!$payment || !$expense || $payment->id === $expense->id) {
                $this->fail('حدد حساب صرف متاحًا من المصدر المختار وحساب تكلفة تفويض نهائيًا مختلفًا في إعدادات الحسابات.');
            }
            $year = $this->years->checkDateRange($data['date']);
            if (!$year) {
                $this->fail('لا توجد سنة مالية نشطة لتاريخ التكلفة.');
            }
            $delegations = IngazDelegation::whereIn('id', $delegationIds)->orderBy('id')->lockForUpdate()->get();
            if ($delegations->count() !== count($delegationIds)) {
                $this->fail('بعض التفويضات غير متاحة لهذا الفرع.');
            }
            foreach ($delegations as $delegation) {
                $invoice = Invoice::where('ingaz_delegation_id', $delegation->id)->lockForUpdate()->first();
                $prefix = "قيد تكلفة تفويض إنجاز #{$delegation->id} / الفاتورة #{$invoice?->id} /";
                if ($delegation->status === IngazDelegation::STATUS_CANCELLED || !$invoice) {
                    $this->fail("التفويض #{$delegation->id} ملغي أو ليست له فاتورة متاحة.");
                }
                if ($invoice->ingaz_delegation_cost_constraint_id || Constraint::where('statement', 'like', $prefix.'%')->exists()) {
                    $this->fail("تكلفة التفويض #{$delegation->id} مسجلة مسبقًا؛ راجع قيد التكلفة الموجود.");
                }
                $amount = round((float) $data['amounts'][$delegation->id], 2);
                $source = ['bank' => 'تحويل بنكي', 'custody' => 'عهدة موظف', 'ingaz' => 'حساب إنجاز'][$data['payment_source_type']];
                $statement = $prefix . " {$source} / مرجع {$data['reference']}";
                $payload = [
                    'date' => $data['date'], 'branch_id' => $delegation->branch_id,
                    'statement' => $statement, 'value' => $amount,
                    'account_id' => [$expense->id, $payment->id],
                    'account_type' => ['debtor', 'creditor'],
                    'debtor_value' => [$amount, 0], 'creditor_value' => [0, $amount],
                    'statement_account' => [$statement, $statement],
                    'cost_center_required' => 'off', 'cost_center_id' => [null, null],
                ];
                $journal = $this->journals->storeConstraint($payload, $year);
                $this->journals->storeConstraintDetails($payload['account_id'], $journal->id, $payload);
                $invoice->update(['ingaz_delegation_cost_constraint_id' => $journal->id]);
                IngazDelegationTimeline::record($delegation, 'delegation_cost_confirmed', [
                    'summary' => "تم تأكيد تكلفة التفويض بمبلغ {$amount} من {$source}، مرجع {$data['reference']}، قيد #{$journal->id}.",
                ]);
            }

            $currentStatuses = [
                ContractStatusEnum::PendingMaidSelection->value,
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Sari->value,
            ];
            $contracts = Contract::query()->without('info')
                ->whereIn('id', $contractIds)
                ->whereIn('type', ['recruitment', 'maid'])
                ->whereIn('status', $currentStatuses)
                ->whereDoesntHave('travelHistory', fn ($q) => $q->where('status', 'completed'))
                ->orderBy('id')->lockForUpdate()->get();
            if ($contracts->count() !== count($contractIds)) {
                $this->fail('بعض عقود الاستقدام غير سارية أو غير متاحة لهذا الفرع.');
            }
            foreach ($contracts as $contract) {
                $invoice = Invoice::where('contract_id', $contract->id)->lockForUpdate()->first();
                $prefix = "قيد تكلفة تفويض عقد الاستقدام #{$contract->id} / الفاتورة #{$invoice?->id} /";
                if (!$invoice) {
                    $this->fail("عقد الاستقدام #{$contract->id} ليست له فاتورة متاحة.");
                }
                if ($invoice->ingaz_delegation_cost_constraint_id || Constraint::where('statement', 'like', $prefix.'%')->exists()) {
                    $this->fail("تكلفة التفويض لعقد الاستقدام #{$contract->id} مسجلة مسبقًا؛ راجع قيد التكلفة الموجود.");
                }
                $amount = round((float) $data['contract_amounts'][$contract->id], 2);
                $source = ['bank' => 'تحويل بنكي', 'custody' => 'عهدة موظف', 'ingaz' => 'حساب إنجاز'][$data['payment_source_type']];
                $statement = $prefix . " {$source} / مرجع {$data['reference']}";
                $payload = [
                    'date' => $data['date'], 'branch_id' => $contract->branch_id,
                    'statement' => $statement, 'value' => $amount,
                    'account_id' => [$expense->id, $payment->id],
                    'account_type' => ['debtor', 'creditor'],
                    'debtor_value' => [$amount, 0], 'creditor_value' => [0, $amount],
                    'statement_account' => [$statement, $statement],
                    'cost_center_required' => 'off', 'cost_center_id' => [null, null],
                ];
                $journal = $this->journals->storeConstraint($payload, $year);
                $this->journals->storeConstraintDetails($payload['account_id'], $journal->id, $payload);
                $invoice->update(['ingaz_delegation_cost_constraint_id' => $journal->id]);
            }

            return $itemCount;
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cost' => $message]);
    }
}
