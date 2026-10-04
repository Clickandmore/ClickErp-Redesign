<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\ContractReturnSettlement;
use App\Models\ContractReturnSettlementLine;
use App\Models\ReturnReasonCode;
use App\Services\ingaz\PostArrivalRefundService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\Account;

class PostArrivalRefundController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractReturnSettlement::query()->with(['contract.user', 'contract.maid', 'reasonCode', 'createdBy', 'approvedBy'])->withCount('lines')->latest('id');
        $this->applyRequestBranchFilter($query, $request, 'contract_return_settlements');
        if ($request->filled('status')) $query->where('status', (string) $request->input('status'));
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($q) use ($term) {
                if (ctype_digit($term)) $q->orWhere('id', (int) $term)->orWhere('contract_id', (int) $term);
                $q->orWhereHas('contract.user', fn ($x) => $x->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('contract.maid', fn ($x) => $x->where('name', 'like', "%{$term}%"));
            });
        }
        return view('admin.ingaz.post_arrival_refunds.index', ['settlements' => $query->paginate(20)->withQueryString()]);
    }

    public function create(Request $request, PostArrivalRefundService $service, ?ContractReturnSettlement $draft = null)
    {
        $contractId = $request->filled('contract_id') ? (int) $request->input('contract_id') : null;
        $contractRelations = ['user', 'maid.office', 'maid.nationality', 'invoice.recruitmentCreditNotes', 'info', 'marketer', 'branch', 'returnRequests'];
        $contracts = Contract::query()->with($contractRelations)
            ->whereIn('type', ['recruitment', 'maid'])->whereHas('invoice')->whereHas('info', fn ($q) => $q->whereNotNull('arrival_date'))
            ->whereDoesntHave('returnSettlements', fn ($q) => $q->whereNotIn('status', ['cancelled']))
            ->latest('id')->limit(150)->get();
        if ($draft) {
            $contractId = $draft->contract_id;
            $contracts = collect([$draft->contract->load($contractRelations)]);
        }
        $selected = $contractId ? $contracts->firstWhere('id', $contractId) : null;
        if ($contractId && ! $selected) {
            $selected = Contract::query()->with($contractRelations)
                ->whereIn('type', ['recruitment', 'maid'])
                ->whereHas('invoice')
                ->whereHas('info', fn ($q) => $q->whereNotNull('arrival_date'))
                ->whereDoesntHave('returnSettlements', fn ($q) => $q->whereNotIn('status', ['cancelled']))
                ->find($contractId);
            if ($selected) $contracts->prepend($selected);
        }
        $proposal = $selected ? $service->proposal($selected, $draft?->settlement_date->toDateString() ?? now()->toDateString()) : null;
        $accountsQuery = Account::query()->whereDoesntHave('children');
        $accounts = $accountsQuery->orderBy('code')->orderBy('id')->get(['id', 'code', 'name']);
        $suggestedAccountId = (int) ($proposal['office_charge_credit_account_id'] ?? 0);
        if ($suggestedAccountId > 0 && ! $accounts->contains('id', $suggestedAccountId)) {
            $suggestedAccount = Account::query()->whereKey($suggestedAccountId)->whereDoesntHave('children')->first(['id', 'code', 'name']);
            if ($suggestedAccount) $accounts->push($suggestedAccount);
        }
        $accounts = $accounts
            ->sort(fn ($left, $right) => strnatcasecmp((string) $left->code, (string) $right->code) ?: strnatcasecmp((string) $left->name, (string) $right->name))
            ->values();

        return view('admin.ingaz.post_arrival_refunds.create', [
            'contracts' => $contracts, 'selected' => $selected, 'proposal' => $proposal,
            'reasons' => ReturnReasonCode::query()->where(function ($query) use ($draft) {
                $query->where('is_active', true);
                if ($draft) $query->orWhere('id', $draft->return_reason_code_id);
            })->orderBy('sort_order')->get(),
            'accounts' => $accounts, 'draft' => $draft?->load('lines'),
        ]);
    }

    public function searchContracts(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $query = Contract::query()->with(['user', 'maid', 'info'])
            ->whereIn('type', ['recruitment', 'maid'])->whereHas('invoice')->whereHas('info', fn ($q) => $q->whereNotNull('arrival_date'))
            ->whereDoesntHave('returnSettlements', fn ($q) => $q->whereNotIn('status', ['cancelled']));
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                if (ctype_digit($term)) $q->orWhere('id', (int) $term);
                $q->orWhereHas('user', fn ($x) => $x->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('maid', fn ($x) => $x->where('name', 'like', "%{$term}%")->orWhere('passport_num', 'like', "%{$term}%"))
                    ->orWhereHas('info', fn ($x) => $x->where('msaned_number', 'like', "%{$term}%"));
            });
        }
        return response()->json(['results' => $query->latest('id')->limit(30)->get()->map(fn ($contract) => [
            'id' => $contract->id,
            'text' => '#' . $contract->id . ' — ' . ($contract->user?->name ?: '-') . ' — ' . ($contract->maid?->name ?: '-') . ' — وصول ' . ($contract->info?->arrival_date ?: '-'),
        ])->values()]);
    }

    public function proposal(Request $request, PostArrivalRefundService $service)
    {
        $data = $request->validate(['contract_id' => 'required|integer|exists:contracts,id', 'date' => 'required|date']);
        return response()->json(['data' => $service->proposal(Contract::query()->findOrFail($data['contract_id']), $data['date'])]);
    }

    public function store(Request $request, PostArrivalRefundService $service)
    {
        $data = $this->draftData($request);
        $settlement = $service->createDraft(Contract::query()->findOrFail($data['contract_id']), $data);
        return redirect()->route('post-arrival-refunds.show', $settlement)->with('success', 'تم إنشاء مسودة الاسترداد من دون إنشاء أي قيد.');
    }

    public function edit(Request $request, ContractReturnSettlement $postArrivalRefund, PostArrivalRefundService $service)
    {
        abort_unless($postArrivalRefund->status === 'draft', 409, 'يمكن تعديل المسودة فقط.');
        return $this->create($request, $service, $postArrivalRefund);
    }

    public function update(Request $request, ContractReturnSettlement $postArrivalRefund, PostArrivalRefundService $service)
    {
        abort_unless($postArrivalRefund->status === 'draft', 409, 'يمكن تعديل المسودة فقط.');
        $data = $this->draftData($request);
        if ((int) $data['contract_id'] !== (int) $postArrivalRefund->contract_id) {
            throw ValidationException::withMessages(['contract_id' => 'لا يمكن تغيير العقد المرتبط بالمسودة.']);
        }
        $settlement = $service->updateDraft($postArrivalRefund, $data);
        return redirect()->route('post-arrival-refunds.show', $settlement)->with('success', 'تم تعديل مسودة الاسترداد دون إنشاء أي قيد.');
    }

    private function draftData(Request $request): array
    {
        $data = $request->validate([
            'contract_id' => 'required|integer|exists:contracts,id',
            'return_reason_code_id' => 'required|integer|exists:return_reason_codes,id',
            'settlement_date' => 'required|date', 'reason' => 'required|string|max:2000', 'notes' => 'nullable|string|max:4000',
            'lines' => 'required|array', 'lines.*.enabled' => 'nullable|boolean',
            'lines.*.type' => ['required', Rule::in(['credit_note', 'client_deduction', 'client_payout', 'office_charge', 'shelter_cost', 'marketer_commission', 'adjustment'])],
            'lines.*.approved_amount' => 'nullable|numeric|min:0|max:9999999999.99',
            'lines.*.credit_account_id' => 'nullable|integer|exists:fn_accounts,id',
            'lines.*.credit_account_query' => 'nullable|string|max:500',
            'lines.*.settlement_method' => 'nullable|in:claim,next_dispatch_deduction,financial_request,balance_only',
            'lines.*.reason' => 'nullable|string|max:1000',
        ]);
        foreach ($data['lines'] as $index => &$line) {
            $accountQuery = trim((string) ($line['credit_account_query'] ?? ''));
            if ($accountQuery === '') continue;
            $account = $this->resolveLeafAccountQuery($accountQuery);
            if (! $account) {
                throw ValidationException::withMessages([
                    "lines.{$index}.credit_account_query" => 'اختر حساباً محدداً من نتائج البحث في مربع الحساب.',
                ]);
            }
            $line['credit_account_id'] = (int) $account->id;
        }
        unset($line);
        return $data;
    }

    public function show(ContractReturnSettlement $postArrivalRefund)
    {
        $postArrivalRefund->load(['contract.user', 'contract.maid.office', 'invoice', 'reasonCode', 'createdBy', 'approvedBy', 'lines.debitAccount', 'lines.creditAccount', 'lines.constraint', 'lines.reversalConstraint', 'lines.creditNote', 'lines.financialRequest']);
        return view('admin.ingaz.post_arrival_refunds.show', ['settlement' => $postArrivalRefund]);
    }

    public function post(ContractReturnSettlement $postArrivalRefund, PostArrivalRefundService $service)
    {
        $service->post($postArrivalRefund);
        return back()->with('success', 'تم اعتماد البنود المختارة وإنشاء مستنداتها المالية.');
    }

    public function reverse(Request $request, ContractReturnSettlement $postArrivalRefund, ContractReturnSettlementLine $line, PostArrivalRefundService $service)
    {
        abort_unless((int) $line->settlement_id === (int) $postArrivalRefund->id, 404);
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        $service->reverseLine($line, $data['reason']);
        return back()->with('success', 'تم عكس البند المحدد فقط.');
    }

    public function payout(Request $request, ContractReturnSettlement $postArrivalRefund, PostArrivalRefundService $service)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:9999999999.99', 'method' => 'required|in:financial_request,balance_only']);
        $service->requestPayout($postArrivalRefund, (float) $data['amount'], $data['method']);
        return back()->with('success', $data['method'] === 'financial_request' ? 'تم إنشاء طلب مالي مربوط بالمستند.' : 'تم تثبيت المبلغ كرصيد للعميل من دون طلب مالي.');
    }

    private function resolveLeafAccountQuery(string $value): ?Account
    {
        if (preg_match('/\[#(\d+)\]\s*$/u', $value, $matches)) {
            return Account::query()->whereKey((int) $matches[1])->whereDoesntHave('children')->first();
        }

        $parts = preg_split('/\s+—\s+/u', $value, 2);
        if (count($parts) === 2) {
            return Account::query()->whereDoesntHave('children')
                ->where('code', trim($parts[0]))
                ->where('name', trim($parts[1]))
                ->first();
        }

        $matches = Account::query()->whereDoesntHave('children')
            ->where(function ($query) use ($value) {
                $query->where('code', $value)->orWhere('name', $value);
            })
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
