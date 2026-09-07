<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\{ProcurementDocument, Account, FnYear};
use Nami\Finance\Enums\{AccountTypesEnum, ConstraintTypiesEnum};
class ProcurementService
{
    public static function calculate(array $lines): array
    {
        Validator::make(['lines' => $lines], ['lines' => 'required|array|min:1|max:100', 'lines.*.description' => 'required|string|max:500', 'lines.*.account_id' => 'required|integer', 'lines.*.quantity' => 'required|numeric|min:0.001|max:1000000', 'lines.*.unit_price' => 'required|numeric|min:0|max:100000000', 'lines.*.discount' => 'required|numeric|min:0|max:100000000', 'lines.*.tax_rate' => 'required|numeric|min:0|max:100'])->validate();
        $totals = ['subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0];
        $result = [];
        foreach ($lines as $i => $line) {
            $quantity = round((float) $line['quantity'], 3);
            $price = round((float) $line['unit_price'], 2);
            $gross = (int) round($quantity * $price * 100);
            $discount = (int) round((float) $line['discount'] * 100);
            if ($discount > $gross) {
                throw ValidationException::withMessages(["lines.{$i}.discount" => 'الخصم أكبر من قيمة البند.']);
            }
            $net = $gross - $discount;
            $rate = round((float) $line['tax_rate'], 2);
            $tax = (int) round($net * $rate / 100);
            $result[] = ['description' => $line['description'], 'account_id' => $line['account_id'], 'quantity' => $quantity, 'unit_price' => $price, 'discount' => $discount / 100, 'tax_rate' => $rate, 'net' => $net / 100, 'tax' => $tax / 100, 'total' => ($net + $tax) / 100];
            $totals['subtotal'] += $gross;
            $totals['discount'] += $discount;
            $totals['tax'] += $tax;
            $totals['total'] += $net + $tax;
        }
        if ($totals['total'] <= 0 || $totals['total'] > 99999999999999) {
            throw ValidationException::withMessages(['lines' => 'يجب أن يكون إجمالي المستند موجباً وضمن الحد المسموح.']);
        }
        return ['lines' => $result, 'totals' => array_map(fn($v) => $v / 100, $totals)];
    }
    public function save(array $data, ?int $id = null): ProcurementDocument
    {
        return DB::transaction(function () use ($data, $id) {
            $doc = $id ? ProcurementDocument::lockForUpdate()->findOrFail($id) : new ProcurementDocument();
            if ($id) {
                $this->requireStatus($doc, 'draft');
            }
            $calculated = self::calculate($data['lines']);
            unset($data['lines']);
            $doc->fill($data + $calculated['totals']);
            if (!$id) {
                $doc->created_by_id = auth('admin')->id();
            }
            $doc->save();
            $doc->lines()->delete();
            $doc->lines()->createMany($calculated['lines']);
            return $doc;
        });
    }
    public function transition(int $id, string $action): ProcurementDocument
    {
        return DB::transaction(function () use ($id, $action) {
            $doc = ProcurementDocument::lockForUpdate()->findOrFail($id);
            if ($action === 'convert') {
                $this->requireStatus($doc, 'approved');
                if ($doc->kind !== 'request') {
                    abort(422);
                }
                $invoice = $doc->replicate(['status', 'approved_at', 'approved_by_id', 'constraint_id', 'supplier_account_id']);
                $invoice->kind = 'invoice';
                $invoice->status = 'draft';
                $invoice->source_id = $doc->id;
                $invoice->created_by_id = auth('admin')->id();
                $invoice->save();
                foreach ($doc->lines as $line) {
                    $invoice->lines()->create($line->only(['description', 'account_id', 'quantity', 'unit_price', 'discount', 'tax_rate', 'net', 'tax', 'total']));
                }
                $doc->update(['status' => 'converted']);
                return $invoice;
            }
            $this->requireStatus($doc, 'draft');
            if ($action === 'cancel') {
                $doc->update(['status' => 'cancelled']);
                return $doc;
            }
            if ($doc->kind === 'invoice') {
                $supplier = $doc->supplier()->firstOrFail();
                $this->leafAccount($supplier->account_id);
                $entries = [];
                foreach ($doc->lines as $line) {
                    $this->leafAccount($line->account_id);
                    $entries[] = [$line->account_id, (float) $line->net, 0];
                }
                if ($doc->tax > 0) {
                    $this->leafAccount($doc->tax_account_id);
                    if (!Account::whereKey($doc->tax_account_id)->where('type', AccountTypesEnum::TaxPurchases->value)->exists()) {
                        throw ValidationException::withMessages(['tax_account_id' => 'اختر حساب ضريبة المشتريات.']);
                    }
                    $entries[] = [$doc->tax_account_id, (float) $doc->tax, 0];
                }
                $entries[] = [$supplier->account_id, 0, (float) $doc->total];
                $constraint = $this->journal($doc, $doc->date, (float) $doc->total, 'فاتورة مشتريات ' . $doc->number, $entries, ConstraintTypiesEnum::Purchases->value);
                $doc->constraint_id = $constraint->id;
                $doc->supplier_account_id = $supplier->account_id;
            }
            $doc->status = $doc->kind === 'request' ? 'approved' : 'posted';
            $doc->approved_by_id = auth('admin')->id();
            $doc->approved_at = now();
            $doc->save();
            return $doc;
        });
    }
    public function pay(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data) {
            $doc = ProcurementDocument::lockForUpdate()->findOrFail($id);
            $this->requireStatus($doc, 'posted');
            if ($doc->kind !== 'invoice') {
                abort(422);
            }
            if ($doc->payments()->where('token', $data['token'])->exists()) {
                return;
            }
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > $doc->balance) {
                throw ValidationException::withMessages(['amount' => 'قيمة الدفعة تتجاوز المتبقي أو غير صالحة.']);
            }
            if ($data['date'] < $doc->date) {
                throw ValidationException::withMessages(['date' => 'تاريخ السداد يجب ألا يسبق الفاتورة.']);
            }
            $this->leafAccount($data['account_id']);
            if (!self::cashAccounts()->whereKey($data['account_id'])->exists()) {
                throw ValidationException::withMessages(['account_id' => 'اختر حساب خزينة أو بنك.']);
            }
            $constraint = $this->journal($doc, $data['date'], $amount, 'سداد مشتريات ' . $doc->number, [[$doc->supplier_account_id, $amount, 0], [$data['account_id'], 0, $amount]], ConstraintTypiesEnum::DailyEntry->value);
            $doc->payments()->create($data + ['constraint_id' => $constraint->id, 'created_by_id' => auth('admin')->id()]);
        });
    }
    public static function cashAccounts()
    {
        return Account::whereDoesntHave('children')->whereIn('type', [AccountTypesEnum::Bank->value, AccountTypesEnum::Box->value, AccountTypesEnum::BankName->value]);
    }
    private function leafAccount($id): void
    {
        if (!$id || !Account::whereKey($id)->whereDoesntHave('children')->exists()) {
            throw ValidationException::withMessages(['account_id' => 'يجب ربط المورد وكل بند بحساب فرعي صالح.']);
        }
    }
    private function requireStatus(ProcurementDocument $doc, string $status): void
    {
        if ($doc->status !== $status) {
            throw ValidationException::withMessages(['status' => 'تغيرت حالة المستند أو أن الإجراء غير مسموح في حالته الحالية.']);
        }
    }
    private function journal($doc, $date, $amount, $statement, $entries, $type)
    {
        $year = FnYear::where('status', 1)->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->first();
        if (!$year) {
            throw ValidationException::withMessages(['date' => 'التاريخ خارج السنة المالية النشطة.']);
        }
        $constraint = app(ConstraintService::class)->store(['type' => $type, 'value' => $amount, 'date' => $date, 'statement' => $statement, 'year_id' => $year->id, 'created_by_id' => auth('admin')->id()]);
        foreach ($entries as [$account, $debit, $credit]) {
            if (!$debit && !$credit) {
                continue;
            }
            $constraint->constraintDetails()->create(['account_id' => $account, 'date' => $date, 'year_id' => $year->id, 'cost_center_id' => $doc->cost_center_id, 'statement' => $statement, 'type' => $debit ? 'debtor' : 'creditor', 'debtor_value' => $debit, 'creditor_value' => $credit]);
        }
        return $constraint;
    }
}
