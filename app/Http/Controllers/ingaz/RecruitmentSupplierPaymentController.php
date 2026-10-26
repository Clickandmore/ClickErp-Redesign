<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Support\AutomaticJournalEntries;
use App\Support\RecruitmentAgentBalance;
use App\Support\RecruitmentCostCenters;
use App\Support\RecruitmentSupplierSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\YearService;

/**
 * دفعات الوكيل الخارجي مربوطة بالعقد والعاملة ومركز التكلفة.
 *
 * فاتورة الوكيل تُثبت بالكامل مرة واحدة (مدين تكلفة المبيعات / دائن حساب الوكيل)، ثم
 * تُسدَّد على دفعات. هذه الشاشة تسجّل الدفعة: مدين حساب الوكيل / دائن البنك المسدد.
 *
 * العرض متاح دائماً لأنه كشف أرصدة لا يكتب شيئاً. أما التسجيل فمرهون بإعداد
 * RecruitmentSupplierSettings::PAYMENT_MODE، فالافتراضي (سند الصرف العام) يبقى هو
 * المسار الوحيد حتى يُبدَّل الإعداد عن قصد.
 */
class RecruitmentSupplierPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $contracts = Contract::query()
            ->with(['user', 'maid.nationality', 'requiredMaidNationality', 'externalOfficeDispatch.recruitmentOffice'])
            ->whereNotNull('external_office_cost_constraint_id')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(function ($inner) use ($search, $like) {
                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                    $inner->orWhereHas('user', fn ($q) => $q->where('name', 'like', $like))
                        ->orWhereHas('maid', fn ($q) => $q->where('name', 'like', $like));
                });
            })
            ->latest('id')
            ->paginate(25)
            ->appends($request->query());

        $rows = $contracts->getCollection()->map(fn (Contract $contract) => $this->summarise($contract));

        return view('admin.ingaz.recruitment_supplier_payments.index', [
            'contracts' => $contracts,
            'rows' => $rows,
            'search' => $search,
            'paymentsEnabled' => RecruitmentSupplierSettings::usesContractLinkedPayments(),
            'automaticEntriesEnabled' => AutomaticJournalEntries::enabled(),
        ]);
    }

    public function create(int $contract)
    {
        $this->assertPaymentsEnabled();

        $model = Contract::query()
            ->with(['user', 'maid.nationality', 'requiredMaidNationality', 'externalOfficeDispatch.recruitmentOffice'])
            ->whereNotNull('external_office_cost_constraint_id')
            ->findOrFail($contract);

        return view('admin.ingaz.recruitment_supplier_payments.create', [
            'contract' => $model,
            'summary' => $this->summarise($model),
            'bankAccounts' => $this->bankAccounts(),
        ]);
    }

    public function store(Request $request, int $contract, BondService $bondService, YearService $yearService)
    {
        $this->assertPaymentsEnabled();

        $model = Contract::query()
            ->whereNotNull('external_office_cost_constraint_id')
            ->findOrFail($contract);

        $validated = $request->validate([
            'date' => 'required|date',
            'value' => 'required|numeric|min:0.01',
            'bank_account_id' => 'required|integer|exists:fn_accounts,id',
            'statement' => 'nullable|string|max:500',
        ], [
            'date.required' => 'تاريخ الدفعة مطلوب.',
            'value.required' => 'مبلغ الدفعة مطلوب.',
            'value.min' => 'مبلغ الدفعة يجب أن يكون أكبر من صفر.',
            'bank_account_id.required' => 'حساب البنك المسدد مطلوب.',
        ]);

        $summary = $this->summarise($model);
        $amount = round((float) $validated['value'], 2);

        if ($amount > $summary['remaining'] + 0.001) {
            throw ValidationException::withMessages([
                'value' => 'مبلغ الدفعة أكبر من المتبقي على فاتورة الوكيل (' . number_format($summary['remaining'], 2) . ').',
            ]);
        }

        $bankAccount = $this->bankAccounts()->firstWhere('id', (int) $validated['bank_account_id']);
        if (!$bankAccount) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'حساب البنك المحدد غير صالح للسداد.',
            ]);
        }

        if (!$summary['office_account_id']) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'لا يوجد حساب للوكيل الخارجي على هذا العقد.',
            ]);
        }

        if (!$yearService->checkDateRange($validated['date'])) {
            throw ValidationException::withMessages([
                'date' => 'لا يوجد سنة مالية نشطة للتاريخ المحدد.',
            ]);
        }

        $bond = DB::transaction(function () use ($model, $summary, $amount, $validated, $bankAccount, $bondService) {
            Contract::query()->whereKey($model->id)->lockForUpdate()->firstOrFail();

            $costCenterId = RecruitmentCostCenters::forContract((int) $model->id, $summary['nationality'])->id;
            $statement = $this->buildStatement($model, $summary, $validated['statement'] ?? null);

            $bond = $bondService->storeSarfBond([
                'payment_type' => BondPaymentTypes::Cash->value,
                'bond_book_num' => ' ',
                'debit_account_id' => $summary['office_account_id'],
                'credit_account_id' => $bankAccount->id,
                'value' => $amount,
                'date' => $validated['date'],
                'statement' => $statement,
                'code' => $bondService->lastCode(BondTypes::Sarf->value),
                'is_tax' => 0,
                'general_type' => 0,
                'cost_center_id' => $costCenterId,
            ]);

            $bond = $bond->fresh();

            if (Schema::hasColumn('fn_bonds', 'recruitment_contract_id')) {
                $bond->update(['recruitment_contract_id' => $model->id]);
            }

            // دفعة الوكيل سند مُعتمد بطبيعته: هي تسجيل لتحويل حدث فعلاً.
            if (AutomaticJournalEntries::enabled() && ((int) $bond->is_confirm !== 1 || !$bond->constraint_id)) {
                $bondService->confirmBond($bond->id);
                $bond = $bond->fresh();
            }

            // BondService لا يكتب مركز التكلفة على أسطر القيد، وهو المطلوب لتفصيل
            // الدولة والعقد في التقارير، فيُستكمل هنا دون تعديل خدمة السندات.
            if ($bond->constraint_id) {
                ConstraintDetails::query()
                    ->where('constraint_id', $bond->constraint_id)
                    ->update(['cost_center_id' => $costCenterId]);
            }

            return $bond;
        });

        $returnContract = $request->input('return_to_contract');
        $redirect = is_numeric($returnContract)
            ? redirect()->route('recruitment-contract-finance.show', (int) $returnContract)
            : redirect()->route('recruitment-supplier-payments.index');

        return $redirect
            ->with('success', 'تم تسجيل دفعة الوكيل الخارجي رقم ' . $bond->code . ' بمبلغ ' . number_format((float) $bond->value, 2) . '.');
    }

    private function assertPaymentsEnabled(): void
    {
        if (!RecruitmentSupplierSettings::usesContractLinkedPayments()) {
            abort(403, 'شاشة دفعات الوكيل الخارجي غير مفعلة. فعّلها من إعدادات الحسابات، قسم «قيود المشتريات والوكلاء الخارجيون».');
        }
    }

    private function summarise(Contract $contract): array
    {
        return RecruitmentAgentBalance::for($contract);
    }

    private function bankAccounts()
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->whereIn('type', [
                AccountTypesEnum::Banks->value,
                AccountTypesEnum::Bank->value,
                AccountTypesEnum::Boxs->value,
                AccountTypesEnum::Box->value,
                AccountTypesEnum::BankName->value,
            ])
            ->orderBy('code')
            ->get();
    }

    private function buildStatement(Contract $contract, array $summary, ?string $note): string
    {
        $statement = 'سداد دفعة للوكيل الخارجي'
            . ' / عقد استقدام #' . $contract->id
            . ' / العاملة: ' . ($summary['worker'] !== '' ? $summary['worker'] : '-')
            . ' / ' . ($summary['nationality'] !== '' ? $summary['nationality'] : '-')
            . ' / المكتب: ' . $summary['office'];

        $note = trim((string) $note);

        return $note !== '' ? $statement . ' / ' . $note : $statement;
    }
}
