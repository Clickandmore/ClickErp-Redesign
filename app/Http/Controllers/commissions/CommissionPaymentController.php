<?php

namespace App\Http\Controllers\commissions;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Services\commissions\CommissionPaymentService;
use App\Services\commissions\CommissionProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\CostCenter;

class CommissionPaymentController extends Controller
{
    public function __construct(
        private readonly CommissionPaymentService $paymentService,
        private readonly CommissionProfileService $profileService
    ) {}

    public function financialIndex()
    {
        $overview = $this->paymentService->getFinancialOverview();

        return view('commissions.finance.index', $overview);
    }

    public function financialOptions(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['account', 'cost_center'])],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim((string) ($validated['q'] ?? ''));
        $page = (int) ($validated['page'] ?? 1);

        if ($validated['type'] === 'account') {
            $query = Account::query()
                ->select(['id', 'name', 'code'])
                ->whereDoesntHave('children');
            if ($search !== '') {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            }
            $paginator = $query->orderBy('code')->paginate(30, ['*'], 'page', $page);
            $results = collect($paginator->items())->map(fn ($item) => [
                'id' => $item->id,
                'text' => trim(($item->code ? $item->code . ' - ' : '') . $item->name),
            ]);
        } else {
            $query = CostCenter::query()->select(['id', 'title', 'code']);
            if ($search !== '') {
                $query->where(fn ($q) => $q
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            }
            $paginator = $query->orderBy('code')->paginate(30, ['*'], 'page', $page);
            $results = collect($paginator->items())->map(fn ($item) => [
                'id' => $item->id,
                'text' => trim(($item->code ? $item->code . ' - ' : '') . $item->title),
            ]);
        }

        return response()->json([
            'results' => $results->values(),
            'pagination' => ['more' => $paginator->hasMorePages()],
        ]);
    }

    public function index(Admin $admin)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);

        $payments = $this->paymentService->getPaymentsForMarketer($admin->id);
        $balance = $this->profileService->getMarketerBalance($admin->id);
        $summary = $this->paymentService->getPaymentSummary($admin->id);
        $financialForm = $this->paymentService->getFinancialFormData();

        return view('commissions.payments.index', compact('admin', 'payments', 'balance', 'summary', 'financialForm'));
    }

    public function store(Request $request, Admin $admin)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);

        $balance = $this->profileService->getMarketerBalance($admin->id);

        $validated = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:' . $balance],
            'payment_date' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ] + $this->financialRules());

        $payment = $this->paymentService->issuePayment(
            $admin->id,
            (float) $validated['amount'],
            $validated['payment_date'],
            $validated['notes'] ?? null,
            auth('admin')->id(),
            $validated
        );

        $message = $payment->bond?->is_confirm
            ? "تم إصدار سند العمولة وسند الصرف المالي #{$payment->bond->code} وإنشاء القيد #{$payment->bond->constraint_id}."
            : "تم إصدار سند العمولة وسند الصرف المالي #{$payment->bond?->code}، وهو بانتظار الاعتماد المالي لإنشاء القيد.";

        return redirect()
            ->route('commissions.marketers.payments.index', $admin->id)
            ->with('success', $message);
    }

    public function linkFinanceBond(Request $request, Admin $admin, int $payment)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);

        $validated = $request->validate($this->financialRules());
        $payment = $this->paymentService->linkExistingPaymentToFinance(
            $admin->id,
            $payment,
            $validated
        );

        $message = $payment->bond?->is_confirm
            ? "تم ترحيل السند إلى المالية وإنشاء القيد #{$payment->bond->constraint_id}."
            : 'تم ترحيل السند إلى سندات الصرف، وهو بانتظار الاعتماد المالي لإنشاء القيد.';

        return redirect()
            ->route('commissions.marketers.payments.index', $admin->id)
            ->with('success', $message);
    }

    public function print(Admin $admin, int $payment)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);

        $payment = $this->paymentService->getPaymentsForMarketer($admin->id)
            ->where('id', $payment)->first();

        abort_if(! $payment, 404);

        return view('commissions.payments.print', compact('admin', 'payment'));
    }

    private function financialRules(): array
    {
        $paymentTypeValues = array_map(
            static fn (BondPaymentTypes $type) => $type->value,
            BondPaymentTypes::cases()
        );

        return [
            'payment_type' => ['required', 'integer', Rule::in($paymentTypeValues)],
            'debit_account_id' => ['required', 'integer', 'exists:fn_accounts,id'],
            'credit_account_id' => ['required', 'integer', 'different:debit_account_id', 'exists:fn_accounts,id'],
            'cost_center_id' => [
                Rule::requiredIf($this->paymentService->isCostCenterRequired()),
                'nullable',
                'integer',
                'exists:fn_cost_centers,id',
            ],
            'transfer_number' => ['nullable', 'string', 'max:100'],
            'check_num' => [
                Rule::requiredIf(fn () => (int) request('payment_type') === BondPaymentTypes::Check->value),
                'nullable',
                'string',
                'max:100',
            ],
            'check_date' => [
                Rule::requiredIf(fn () => (int) request('payment_type') === BondPaymentTypes::Check->value),
                'nullable',
                'date',
            ],
        ];
    }

    private function isCommissionMarketer(Admin $admin): bool
    {
        return $admin->admin_type === 'customer_service'
            || $this->profileService->hasOperations($admin->id);
    }
}
