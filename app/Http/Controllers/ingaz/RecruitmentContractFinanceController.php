<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\NewPermKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class RecruitmentContractFinanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:' . NewPermKeys::REC_CONTRACTS_LATE_FINANCE . ',admin');
    }

    private function contract(int $id, bool $lock = false): Contract
    {
        $query = Contract::query()->with(['user', 'info', 'maid.nationality', 'requiredMaidNationality', 'externalOfficeDispatch', 'invoice.musanedTransferItem.transfer']);
        if ($lock) {
            $query->lockForUpdate();
        }
        $contract = $query->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');
        abort_unless($contract->isLateFinanceEligible(), 422, 'هذه الصفحة مخصصة لعقود الاستقدام بحالة وصول أو مكتملة.');
        return $contract;
    }

    public function show(int $id)
    {
        $contract = $this->contract($id);
        return view('admin.ingaz.RecruitmentContract.financial-operations', [
            'financialContract' => $contract,
            'contractId' => $contract->id,
            'selectedContract' => $contract,
            'nextInvoiceNumber' => ((int) Invoice::query()->max('id')) + 1,
            'invoiceIssuedAt' => now(),
            'invoiceEligibleStatusLabels' => collect(['وصول', 'مكتملة']),
            'invoiceStoreUrl' => route('RecruitmentContract.lateFinance.invoice', $id),
            'dispatchOffice' => $contract->resolveExternalOfficeDispatchOffice(),
            'dispatchSourceErrors' => $contract->externalOfficeDispatch ? [] : $contract->externalOfficeDispatchSourceValidationErrors(),
            'delegationCostDefault' => app(\App\Services\ingaz\DelegationCostService::class)->defaultAmount(),
            'delegationPaymentAccounts' => app(\App\Services\ingaz\DelegationCostService::class)->paymentAccounts(),
            'delegationCostJournal' => $contract->invoice?->ingaz_delegation_cost_constraint_id
                ? \Nami\Finance\Models\Constraint::query()->find($contract->invoice->ingaz_delegation_cost_constraint_id) : null,
        ] + app(MusanedTransferController::class)->financialWorkspaceOptions());
    }

    public function storeInvoice(Request $request, int $id, ConstraintService $service, YearService $yearService, BondService $bondService)
    {
        $this->contract($id);
        $request->validate([
            'contract_id' => ['nullable', 'integer', 'in:' . $id],
            'client_id' => 'prohibited', 'ingaz_delegation_id' => 'prohibited',
            'sponsorship_transfer_id' => 'prohibited',
        ]);
        $request->merge(['contract_id' => $id]);
        $response = app(InvoiceController::class)->store($request, $service, $yearService, $bondService, true);
        if ($response->getStatusCode() === 200) {
            $data = $response->getData(true);
            if ($data['success'] ?? false) {
                $data['financial_workspace_url'] = route('RecruitmentContract.lateFinance.show', $id);
                $response->setData($data);
                session()->flash('success', 'تم إصدار الفاتورة. يمكنك الآن تسجيل تحويل مساند من نفس الصفحة.');
            }
        }
        return $response;
    }

    public function storeTransfer(Request $request, int $id, ConstraintService $service, YearService $yearService)
    {
        return DB::transaction(function () use ($request, $id, $service, $yearService) {
            $contract = $this->contract($id, true);
            abort_unless($contract->invoice, 422, 'يجب إصدار فاتورة العقد قبل تسجيل تحويل مساند.');
            $request->validate([
                'items' => 'required|array|size:1',
                'items.0.contract_id' => ['required', 'integer', 'in:' . $id],
                'items.0.ingaz_delegation_id' => 'prohibited',
            ]);
            app(MusanedTransferController::class)->store($request, $service, $yearService);
            return redirect()->route('RecruitmentContract.lateFinance.show', $id)
                ->with('success', 'تم تسجيل تحويل مساند للعقد بنجاح.');
        });
    }

    public function storeDispatch(Request $request, int $id)
    {
        $validated = $request->validate([
            'dispatch_date' => 'required|date',
            'arrival_station' => 'required|string|max:255',
            'office_contract_cost_usd' => 'required|numeric|min:0.01|max:99999999.99',
        ]);
        return DB::transaction(function () use ($request, $id, $validated) {
            $contract = $this->contract($id, true);
            abort_unless($contract->invoice, 422, 'يجب إصدار فاتورة العقد أولاً قبل تسجيل الإرسالية.');
            $errors = $contract->externalOfficeDispatchSourceValidationErrors();
            if ($errors) {
                throw \Illuminate\Validation\ValidationException::withMessages(['dispatch_date' => implode(' ', $errors)]);
            }
            app(RecruitmentContractExternalOfficeDispatchController::class)->persistExternalOfficeDispatch(
                $contract, $validated['dispatch_date'], trim($validated['arrival_station']),
                number_format((float) $validated['office_contract_cost_usd'], 2, '.', ''), auth('admin')->id(), true
            );
            return redirect()->route('RecruitmentContract.lateFinance.show', $id)
                ->with('success', 'تم تسجيل الإرسالية واستكمال تكلفة المكتب وفق إعدادات القيود المحاسبية.');
        });
    }

    public function storeDelegationCost(Request $request, int $id, \App\Services\ingaz\DelegationCostService $service)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d', 'reference' => 'required|string|max:100',
            'confirmed' => 'accepted', 'payment_source_type' => 'required|in:bank,custody,ingaz',
            'payment_account_id' => 'required|integer',
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);
        return DB::transaction(function () use ($id, $service, $validated) {
            $contract = $this->contract($id, true);
            abort_unless($contract->invoice, 422, 'يجب إصدار فاتورة العقد قبل تسجيل تكلفة التفويض.');
            // Bind the operation to the URL's contract; submitted batch/source IDs are never used.
            $service->post($validated + ['contract_ids' => [$id], 'contract_amounts' => [$id => $validated['amount']]], true);
            return redirect()->route('RecruitmentContract.lateFinance.show', $id)
                ->with('success', 'تم تسجيل تكلفة التفويض وقيدها المحاسبي للعقد بنجاح.');
        });
    }
}
