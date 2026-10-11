<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\ingaz\User;
use App\Models\InvoiceDraft;
use App\PermKeys;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class InvoiceDraftController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:' . PermKeys::INVOICES_DRAFTS_VIEW . '|' . PermKeys::INVOICES_APPROVE . '|' . PermKeys::INVOICES_CREATE_DRAFT . ',admin')->only('index');
        $this->middleware('permission:' . PermKeys::INVOICES_APPROVE . ',admin')->only(['approve', 'returnForCorrection']);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|in:pending,approved,returned',
            'search' => 'nullable|string|max:100',
            'created_by' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);
        $canViewAll = \App\Support\InvoiceAuthorization::canViewDrafts();
        $branchesEnabled = SystemModuleManager::isEnabled('branches');

        $base = InvoiceDraft::query()
            ->when(!$canViewAll, fn ($query) => $query->where('created_by', auth('admin')->id()))
            ->when($filters['search'] ?? null, fn ($query, $search) => $this->applySearch($query, trim($search)))
            ->when($canViewAll && !empty($filters['created_by']), fn ($query) => $query->where('created_by', $filters['created_by']))
            ->when($branchesEnabled && !empty($filters['branch_id']), fn ($query) => $query->where('branch_id', $filters['branch_id']))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

        $statusCounts = (clone $base)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        $drafts = (clone $base)->with(['creator', 'reviewer', 'contract.user', 'client', 'delegation.user', 'branch'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        $creators = $canViewAll
            ? Admin::query()->whereIn('id', InvoiceDraft::query()->select('created_by')->distinct())->orderBy('name')->get(['id', 'name'])
            : collect();
        $branches = $branchesEnabled ? Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en']) : collect();

        return view('admin.ingaz.invoices.drafts', compact('drafts', 'filters', 'statusCounts', 'creators', 'branches', 'branchesEnabled'));
    }

    /** البحث برقم الطلب أو رقم العقد/التفويض أو اسم العميل وجواله (المخزنة داخل payload). */
    private function applySearch($query, string $search)
    {
        $userIds = User::query()->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->limit(500)->pluck('id');
        $contractIds = $userIds->isEmpty() ? collect() : Contract::query()->whereIn('user_id', $userIds)->limit(1000)->pluck('id');
        $delegationIds = $userIds->isEmpty() ? collect() : IngazDelegation::query()->whereIn('user_id', $userIds)->limit(1000)->pluck('id');

        return $query->where(function ($query) use ($search, $userIds, $contractIds, $delegationIds) {
            if (ctype_digit($search)) {
                $query->orWhere('id', (int) $search)
                    ->orWhere('payload->contract_id', (int) $search)
                    ->orWhere('payload->ingaz_delegation_id', (int) $search);
            }
            if ($userIds->isNotEmpty()) $query->orWhereIn('payload->client_id', $userIds->all());
            if ($contractIds->isNotEmpty()) $query->orWhereIn('payload->contract_id', $contractIds->all());
            if ($delegationIds->isNotEmpty()) $query->orWhereIn('payload->ingaz_delegation_id', $delegationIds->all());
            if (!ctype_digit($search) && $userIds->isEmpty()) $query->whereRaw('1 = 0');
        });
    }

    public function approve(Request $request, int $id, ConstraintService $service, YearService $yearService, BondService $bondService)
    {
        DB::transaction(function () use ($id, $service, $yearService, $bondService) {
            $draft = InvoiceDraft::query()->lockForUpdate()->findOrFail($id);
            abort_unless($draft->status === 'pending', 409);
            $response = app(InvoiceController::class)->issueApprovedDraft(new Request($draft->payload), $service, $yearService, $bondService);
            $result = $response->getData(true);
            if ($response->getStatusCode() !== 200 || empty($result['success'])) {
                throw ValidationException::withMessages(['invoice' => $result['message'] ?? 'تعذر إصدار الفاتورة.']);
            }
            $draft->update(['status' => 'approved', 'invoice_id' => $result['data']['id'],
                'reviewed_by' => auth('admin')->id(), 'reviewed_at' => now(), 'reference_key' => null]);
        });
        return back()->with('success', 'تم اعتماد الفاتورة وإصدارها.');
    }

    public function returnForCorrection(Request $request, int $id)
    {
        $data = $request->validate(['review_note' => 'required|string|max:2000']);
        DB::transaction(function () use ($id, $data) {
            $draft = InvoiceDraft::query()->lockForUpdate()->findOrFail($id);
            abort_unless($draft->status === 'pending', 409);
            $draft->update(['status' => 'returned', 'reference_key' => null, 'review_note' => $data['review_note'],
                'reviewed_by' => auth('admin')->id(), 'reviewed_at' => now()]);
        });
        return back()->with('success', 'تم إرجاع المسودة. يمكن إنشاء طلب جديد بعد التصحيح.');
    }
}
