<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use App\Services\BranchAccessService;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Nami\Finance\Models\{ProcurementDocument, Supplier, Account, CostCenter};
use Nami\Finance\Services\ProcurementService;
class ProcurementController extends Controller
{
    public function __construct(private BranchAccessService $branches, private ProcurementService $service)
    {
    }
    public function index(Request $request)
    {
        $query = $this->branches->applyToQuery(ProcurementDocument::query(), $request, 'fn_procurement_documents');
        if (in_array($request->kind, ['request', 'invoice'])) {
            $query->where('kind', $request->kind);
        }
        if (array_key_exists($request->input('status', ''), ProcurementDocument::STATUSES)) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $term = mb_substr($request->search, 0, 100);
            $query->where(fn($q) => $q->where('supplier_reference', 'like', "%{$term}%")->orWhereHas('supplier', fn($s) => $s->where('name', 'like', "%{$term}%"))->orWhere('id', preg_replace('/^(PR|PI)-0*/', '', $term)));
        }
        $documents = $query->with(['supplier', 'payments', 'branch'])->latest('id')->paginate(25)->withQueryString();
        return view('finance::finance.procurement.index', compact('documents') + ['branchFilter' => $this->branches->selectorData($request)]);
    }
    public function create(Request $request)
    {
        $doc = new ProcurementDocument(['kind' => $request->kind === 'request' ? 'request' : 'invoice', 'date' => date('Y-m-d')]);
        return $this->form($doc, $request);
    }
    public function edit(Request $request, $id)
    {
        $doc = $this->document($id);
        abort_unless($doc->status === 'draft', 422);
        return $this->form($doc, $request);
    }
    private function form($doc, $request)
    {
        return view('finance::finance.procurement.form', compact('doc') + ['suppliers' => Supplier::orderBy('name')->get(), 'accounts' => Account::whereDoesntHave('children')->orderBy('code')->get(), 'taxAccounts' => Account::where('type', 10)->whereDoesntHave('children')->get(), 'centers' => CostCenter::orderBy('title')->get(), 'branches' => $this->branches->selectorBranches()]);
    }
    public function store(Request $request)
    {
        $doc = $this->service->save($this->validated($request));
        return $this->redirect($doc);
    }
    public function update(Request $request, $id)
    {
        $doc = $this->document($id);
        // Validate contract ownership against the document's immutable branch and kind.
        $request->merge(['kind' => $doc->kind, 'branch_id' => $doc->branch_id]);
        $data = $this->validated($request, $doc);
        return $this->redirect($this->service->save($data, $doc->id));
    }
    public function show($id)
    {
        $doc = $this->document($id)->load(['lines', 'supplier', 'payments', 'invoice', 'branch', 'constraint']);
        return view('finance::finance.procurement.show', compact('doc') + ['cashAccounts' => ProcurementService::cashAccounts()->orderBy('name')->get()]);
    }
    public function approve($id)
    {
        return $this->change($id, 'approve');
    }
    public function convert($id)
    {
        return $this->change($id, 'convert');
    }
    public function cancel($id)
    {
        return $this->change($id, 'cancel');
    }
    private function change($id, $action)
    {
        $doc = $this->document($id);
        return $this->redirect($this->service->transition($doc->id, $action));
    }
    public function pay(Request $request, $id)
    {
        $doc = $this->document($id);
        $data = $request->validate(['amount' => 'required|numeric|min:0.01|max:999999999999.99', 'date' => 'required|date_format:Y-m-d', 'account_id' => 'required|integer|exists:fn_accounts,id', 'reference' => 'nullable|string|max:255', 'token' => 'required|uuid']);
        $this->service->pay($doc->id, $data);
        return $this->redirect($doc);
    }
    private function document($id)
    {
        $doc = ProcurementDocument::findOrFail($id);
        $this->branches->assertCanAccessModel($doc);
        return $doc;
    }
    private function redirect($doc)
    {
        return redirect()->route('finance.procurement.show', $doc->id)->with('success', 'تم حفظ العملية بنجاح.');
    }
    private function validated(Request $request, ?ProcurementDocument $document = null): array
    {
        $data = $request->validate(['kind' => ['required', Rule::in(['request', 'invoice'])], 'supplier_id' => 'required|integer|exists:fn_suppliers,id', 'supplier_reference' => 'nullable|string|max:255', 'date' => 'required|date_format:Y-m-d', 'due_date' => 'nullable|date_format:Y-m-d|after_or_equal:date', 'notes' => 'nullable|string|max:5000', 'contract_id' => 'nullable|integer|exists:contracts,id', 'branch_id' => 'nullable|integer|exists:branches,id', 'cost_center_id' => 'nullable|integer|exists:fn_cost_centers,id', 'tax_account_id' => 'nullable|integer|exists:fn_accounts,id', 'lines' => 'required|array|min:1|max:100', 'lines.*.description' => 'required|string|max:500', 'lines.*.account_id' => 'required|integer|exists:fn_accounts,id', 'lines.*.quantity' => 'required|numeric|min:0.001|max:1000000', 'lines.*.unit_price' => 'required|numeric|min:0|max:100000000', 'lines.*.discount' => 'required|numeric|min:0|max:100000000', 'lines.*.tax_rate' => 'required|numeric|min:0|max:100']);
        $branchId = $this->branches->adminBranchId();
        if ($branchId && !$this->branches->canViewAllBranches()) {
            $data['branch_id'] = $branchId;
        } else {
            $data['branch_id'] = $data['branch_id'] ?? $branchId ?? $this->branches->mainBranchId();
        }
        if ($document) {
            $data['branch_id'] = $document->branch_id;
        }
        if (!empty($data['contract_id'])) {
            $contract = Contract::findOrFail($data['contract_id']);
            $this->branches->assertCanAccessModel($contract);
            if ($contract->branch_id && (int) $contract->branch_id !== (int) $data['branch_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['contract_id' => 'العقد يتبع فرعاً مختلفاً عن المستند.']);
            }
        }
        return $data;
    }
}
