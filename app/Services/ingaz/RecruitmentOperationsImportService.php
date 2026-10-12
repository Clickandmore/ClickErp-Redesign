<?php

namespace App\Services\ingaz;

use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController;
use App\Models\Contract;
use App\Models\MusanedTransfer;
use App\Support\AutomaticJournalEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

/** Business references are resolved read-only; approval reuses the normal creation paths. */
class RecruitmentOperationsImportService
{
    public function resolve(string $type, array $row): array
    {
        $kind = $type === 'external_office_dispatches' ? 'عقد استقدام' : ($row['reference_type'] ?? '');
        $reference = $row;
        $reference['invoice_reference_type'] = $kind;
        $reference['import_reference'] = 'operation-reference';
        $result = app(ImportReferenceResolver::class)->resolve('invoices', $reference);
        foreach (['contract_id', 'ingaz_delegation_id'] as $key) {
            if (isset($result['row'][$key])) $row[$key] = $result['row'][$key];
        }
        $issues = $result['issues'];
        if ($type === 'musaned_transfers' && !in_array($kind, ['عقد استقدام', 'تفويض'], true)) {
            $issues[] = 'نوع الربط مطلوب: عقد استقدام أو تفويض.';
        }
        if ($type === 'musaned_transfers' && filled($row['bank_account_code'] ?? null)) {
            $accounts = Account::query()->where('code', ImportValueParser::digits($row['bank_account_code']))->limit(2)->get();
            if ($accounts->count() !== 1) $issues[] = 'كود الحساب البنكي غير موجود أو غير فريد.';
            else {
                $row['bank_account_id'] = $accounts->first()->id;
                $result['links'][] = ['label' => 'الحساب البنكي', 'id' => $accounts->first()->id,
                    'number' => $accounts->first()->code, 'name' => $accounts->first()->name];
            }
        }
        if ($type === 'musaned_transfers' && !$issues) {
            $key = !empty($row['contract_id']) ? 'contract_id' : 'ingaz_delegation_id';
            if (!empty($row[$key])) {
                $invoices = app(MusanedTransferController::class)->getEligibleInvoicesQuery()->where($key, $row[$key])->limit(2)->get();
                if ($invoices->count() === 1) {
                    $invoice = $invoices->first();
                    $result['links'][] = ['label' => 'الفاتورة', 'id' => $invoice->id,
                        'number' => $invoice->invoice_number ?: $invoice->id,
                        'name' => 'الإجمالي: ' . number_format((float) $invoice->total_due, 2) . ' ريال'];
                }
            }
        }
        return ['row' => $row, 'issues' => array_values(array_unique($issues)), 'links' => $result['links']];
    }

    public function validate(string $type, array $row): array
    {
        try {
            $data = $this->normalized($type, $row);
            if ($type === 'musaned_transfers') {
                Validator::make($data, [
                    'reference_number' => 'required|string|max:255', 'transfer_date' => 'required|date',
                    'transferred_amount' => 'required|numeric|min:0.01',
                    'bank_account_id' => AutomaticJournalEntries::enabled() ? 'required|integer' : 'nullable|integer',
                ], [], ['reference_number' => 'مرجع التحويل', 'transfer_date' => 'تاريخ التحويل',
                    'transferred_amount' => 'المبلغ المحول', 'bank_account_id' => 'كود الحساب البنكي'])->validate();
                if (MusanedTransfer::query()->where('reference_number', $data['reference_number'])->exists()) {
                    throw new \InvalidArgumentException('مرجع التحويل موجود بالفعل؛ راجع تحويل مساند السابق.');
                }
                $controller = app(MusanedTransferController::class);
                if (!empty($data['bank_account_id']) && !$controller->findValidBankAccount((int) $data['bank_account_id'])) {
                    throw new \InvalidArgumentException('الحساب البنكي يجب أن يكون حساب بنك أو صندوق نهائيًا صالحًا.');
                }
                if (AutomaticJournalEntries::enabled() && !app(YearService::class)->checkDateRange($data['transfer_date'])) {
                    throw new \InvalidArgumentException('لا توجد سنة مالية نشطة تشمل تاريخ التحويل.');
                }
                $key = !empty($row['contract_id']) ? 'contract_id' : 'ingaz_delegation_id';
                if (empty($row[$key])) throw new \InvalidArgumentException('لم يتم ربط التحويل بعقد أو تفويض.');
                $invoices = $controller->getEligibleInvoicesQuery()->where($key, $row[$key])->limit(2)->get();
                if ($invoices->count() !== 1) {
                    throw new \InvalidArgumentException('يلزم وجود فاتورة واحدة صالحة لم يسبق تحويلها من مساند؛ راجع فواتير المرجع والتحويلات السابقة.');
                }
                $invoice = $invoices->first();
                $controller->validateImportAccounting($invoice, (int) ($data['bank_account_id'] ?? 0));
                $amount = round($data['transferred_amount'], 2);
                if ($amount > round((float) $invoice->total_due, 2)) throw new \InvalidArgumentException('المبلغ المحول أكبر من إجمالي الفاتورة.');
                if ($invoice->musaned_commission_amount !== null
                    && abs(round((float) $invoice->total_due - (float) $invoice->musaned_commission_amount, 2) - $amount) > 0.01) {
                    throw new \InvalidArgumentException('المبلغ المحول لا يطابق صافي الفاتورة بعد عمولة مساند المثبتة.');
                }
            } else {
                Validator::make($data, [
                    'dispatch_date' => 'required|date', 'arrival_station' => 'required|string|max:255',
                    'office_contract_cost_usd' => 'required|numeric|min:0.01|max:99999999.99',
                    'supplier_recognition_mode' => 'nullable|in:full,partial',
                    'supplier_total_cost_usd' => 'nullable|numeric|min:0.01|max:99999999.99',
                ], [], ['dispatch_date' => 'تاريخ الإرسالية', 'arrival_station' => 'محطة الوصول',
                    'office_contract_cost_usd' => 'تكلفة المورد بالدولار'])->validate();
                $contract = Contract::query()->find($row['contract_id'] ?? null);
                if (!$contract) throw new \InvalidArgumentException('عقد الاستقدام غير موجود أو خارج الفروع المتاحة.');
                if ($contract->externalOfficeDispatch()->exists()) throw new \InvalidArgumentException('توجد إرسالية / فاتورة مورد لهذا العقد بالفعل؛ عدّل السجل الموجود من صفحة العقد.');
                if (!$contract->canManageExternalOfficeDispatch()) throw new \InvalidArgumentException('حالة العقد لا تسمح بإنشاء إرسالية من شاشة الإرساليات.');
                $errors = $contract->externalOfficeDispatchSourceValidationErrors();
                if ($errors) throw new \InvalidArgumentException(implode('؛ ', $errors));
                if (!empty($data['supplier_recognition_mode'])) {
                    if (!\App\Support\InvoiceSupplierWorkflow::enabled()) throw new \InvalidArgumentException('التكلفة الكاملة / الجزئية تتطلب تفعيل التسلسل الجديد في الإعدادات المالية.');
                    if (empty($data['supplier_total_cost_usd']) || ($data['supplier_recognition_mode'] === 'partial'
                        && $data['office_contract_cost_usd'] >= $data['supplier_total_cost_usd'])) {
                        throw new \InvalidArgumentException('إجمالي تكلفة المورد مطلوب؛ المبلغ الجزئي يجب أن يكون أقل من الإجمالي.');
                    }
                }
            }
            return [];
        } catch (ValidationException $exception) {
            return \Illuminate\Support\Arr::flatten($exception->errors());
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            return [$exception->getMessage()];
        }
    }

    public function import(string $type, array $row, ?int $adminId): string
    {
        $permissions = $type === 'musaned_transfers'
            ? [\App\PermKeys::MUSANED_TRANSFERS_CREATE, \App\PermKeys::MUSANED_TRANSFERS_CONTROL, \App\PermKeys::REC_CONTRACTS_CREATE, \App\PermKeys::REC_CONTRACTS_MANAGE]
            : [\App\NewPermKeys::REC_CONTRACTS_UPDATE, \App\NewPermKeys::REC_CONTRACTS_MANAGE];
        if (!AdminGuardHasAnyPerm($permissions)) {
            throw ValidationException::withMessages(['row' => 'اعتماد الاستيراد يتطلب أيضًا صلاحية إنشاء تحويل مساند أو تحديث عقد الاستقدام حسب نوع البيانات.']);
        }
        // Locks prevent two different batches from creating the same operation concurrently.
        if (!empty($row['contract_id'])) Contract::query()->whereKey($row['contract_id'])->lockForUpdate()->firstOrFail();
        elseif (!empty($row['ingaz_delegation_id'])) \App\Models\IngazDelegation::query()->whereKey($row['ingaz_delegation_id'])->lockForUpdate()->firstOrFail();
        $issues = $this->validate($type, $row);
        if ($issues) throw ValidationException::withMessages(['row' => $issues]);
        $data = $this->normalized($type, $row);
        if ($type === 'musaned_transfers') {
            $data['items'] = [[
                'contract_id' => $row['contract_id'] ?? null,
                'ingaz_delegation_id' => $row['ingaz_delegation_id'] ?? null,
                'transferred_amount' => $data['transferred_amount'],
            ]];
            app(MusanedTransferController::class)->createTransfer(new Request($data), app(ConstraintService::class), app(YearService::class));
        } else {
            app(RecruitmentContractExternalOfficeDispatchController::class)->persistExternalOfficeDispatch(
                Contract::query()->findOrFail($row['contract_id']), $data['dispatch_date'], trim($data['arrival_station']),
                (string) $data['office_contract_cost_usd'], $adminId, false,
                $data['supplier_recognition_mode'] ?: null, $data['supplier_total_cost_usd']
            );
        }
        return 'created';
    }

    private function normalized(string $type, array $row): array
    {
        $dateKey = $type === 'musaned_transfers' ? 'transfer_date' : 'dispatch_date';
        $row[$dateKey] = ImportValueParser::date($row[$dateKey] ?? null)?->toDateString();
        foreach ($type === 'musaned_transfers' ? ['transferred_amount'] : ['office_contract_cost_usd', 'supplier_total_cost_usd'] as $key) {
            $row[$key] = ImportValueParser::decimal($row[$key] ?? null);
        }
        if ($type === 'external_office_dispatches') {
            $mode = trim((string) ($row['supplier_recognition_mode'] ?? ''));
            $row['supplier_recognition_mode'] = ['كاملة' => 'full', 'جزئية' => 'partial'][$mode] ?? $mode;
        }
        return $row;
    }
}
