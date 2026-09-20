<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractEscapeReport;
use App\PermKeys;
use App\Services\ManagedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentEscapeReportController extends Controller
{
    use \App\Support\RecruitmentEscapeFormula;

    private function checkPermission(string $permission): void
    {
        abort_unless(@AdminGuardHasPerm($permission), 403);
    }

    private function findContract($id): Contract
    {
        $contract = Contract::whereIn('type', ['recruitment', 'maid'])->findOrFail($id);
        $this->assertCanAccessBranchModel($contract, 'contracts');
        return $contract;
    }

    public function index(Request $request)
    {
        $this->checkPermission(PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW);
        $contracts = Contract::query();
        $this->applyRequestBranchFilter($contracts, $request, 'contracts');
        $recruitment = DB::table('contract_escape_reports')
            ->whereIn('contract_id', (clone $contracts)->whereIn('type', ['recruitment', 'maid'])->select('contracts.id'))
            ->selectRaw("id, contract_id, escape_date, reported_by, 'recruitment' as source");
        $shelter = DB::table('contract_infos')
            ->where('outside_shelter_reason', 'escape_report')
            ->whereIn('contract_id', (clone $contracts)->select('contracts.id'))
            ->selectRaw("id, contract_id, outside_shelter_at as escape_date, outside_shelter_by as reported_by, 'shelter' as source");

        $reports = DB::query()->fromSub($recruitment->unionAll($shelter), 'escape_reports')
            ->orderByDesc('escape_date')->orderBy('source')->orderByDesc('id')->paginate(25);
        $pageContracts = (clone $contracts)->with(['user', 'maid'])
            ->whereIn('id', $reports->getCollection()->pluck('contract_id'))->get()->keyBy('id');
        $reporters = \App\Models\auth\Admin::whereIn('id', $reports->getCollection()->pluck('reported_by')->filter())
            ->get(['id', 'name'])->keyBy('id');
        $reports->getCollection()->each(function ($report) use ($pageContracts, $reporters) {
            $report->contract = $pageContracts->get($report->contract_id);
            $report->reporter = $reporters->get($report->reported_by);
            $report->escape_date = $report->escape_date ? \Carbon\Carbon::parse($report->escape_date) : null;
        });
        return view('admin.ingaz.RecruitmentContract.escape-reports', compact('reports'));
    }

    public function show($contractId)
    {
        abort_unless(@AdminGuardHasPerm(PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW)
            || @AdminGuardHasPerm(PermKeys::REC_CONTRACTS_ESCAPE_REPORT_CREATE), 403);
        $contract = $this->findContract($contractId);
        $report = $contract->escapeReports()->with('reporter')->first();
        if ($report) {
            $this->checkPermission(PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW);
        }
        $formula = $this->buildEscapeCompensationFormula($contract);
        return view('admin.ingaz.RecruitmentContract.escape-report', compact('contract', 'report', 'formula'));
    }

    public function store(Request $request, $contractId, ManagedFileStorage $storage)
    {
        $this->checkPermission(PermKeys::REC_CONTRACTS_ESCAPE_REPORT_CREATE);
        $contract = $this->findContract($contractId);
        $data = $request->validate([
            'escape_date' => 'required|date|before_or_equal:' . now()->addDays(5)->toDateString(),
            'notes' => 'nullable|string',
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);
        $attachments = [];
        try {
            DB::transaction(function () use ($contract, $request, $storage, $data, &$attachments) {
                $contract = Contract::lockForUpdate()->findOrFail($contract->id);
                if ($contract->status !== 'completed') {
                    throw ValidationException::withMessages(['contract' => 'يمكن إضافة بلاغ هروب للعقود المكتملة فقط']);
                }
                if ($contract->escapeReports()->exists()) {
                    throw ValidationException::withMessages(['contract' => 'يوجد بلاغ هروب مسجل لهذا العقد. يرجى حذف البلاغ الحالي قبل إضافة بلاغ جديد.']);
                }
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = [
                        'path' => $storage->uploadFile($file, 'recruitment-contracts/escape-reports'),
                        'name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(),
                    ];
                }
                $formulaNote = $this->formatEscapeCompensationFormulaNote($this->buildEscapeCompensationFormula($contract, $data['escape_date']));
                $contract->escapeReports()->create([
                    'escape_date' => $data['escape_date'], 'contract_status_at_escape' => 'داخل الضمان',
                    'process_status' => 'escape_reported', 'reported_by' => auth('admin')->id(),
                    'notes' => $formulaNote . (filled($data['notes'] ?? null) ? "\n\n" . $data['notes'] : ''),
                    'attachments' => $attachments,
                ]);
                $contract->touch();
            });
        } catch (\Throwable $error) {
            foreach ($attachments as $attachment) { $storage->delete($attachment['path']); }
            throw $error;
        }
        $route = @AdminGuardHasPerm(PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW)
            ? 'RecruitmentContract.escapeReports.show' : 'RecruitmentContract.index';
        return redirect()->route($route, $route === 'RecruitmentContract.index' ? [] : [$contract->id])
            ->with('success', 'تم حفظ بلاغ الهروب بنجاح');
    }

    public function destroy($contractId, $reportId, ManagedFileStorage $storage)
    {
        $this->checkPermission(PermKeys::REC_CONTRACTS_ESCAPE_REPORT_DELETE);
        $contract = $this->findContract($contractId);
        $attachments = DB::transaction(function () use ($contract, $reportId) {
            Contract::lockForUpdate()->findOrFail($contract->id);
            $report = $contract->escapeReports()->findOrFail($reportId);
            $attachments = $report->attachments;
            $report->delete();
            $contract->touch();
            return $attachments;
        });
        foreach ($attachments as $attachment) { $storage->delete($attachment['path']); }
        return redirect()->route('RecruitmentContract.index')->with('success', 'تم حذف بلاغ الهروب وإرجاع عرض العقد إلى حالته الأصلية.');
    }

    public function attachment($contractId, $reportId, int $attachment, ManagedFileStorage $storage)
    {
        $this->checkPermission(PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW);
        $contract = $this->findContract($contractId);
        $report = $contract->escapeReports()->findOrFail($reportId);
        $file = $report->attachments[$attachment] ?? null;
        abort_unless($file && $storage->exists($file['path']), 404);
        return $storage->downloadResponse($file['path'], $file['name'], $file['mime_type']);
    }
}
