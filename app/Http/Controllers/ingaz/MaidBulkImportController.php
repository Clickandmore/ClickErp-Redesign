<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\BulkMaidImportUploadRequest;
use App\Models\ingaz\MaidBulkImport;
use App\Services\ingaz\MaidBulkImportService;

class MaidBulkImportController extends Controller
{
    public function create(MaidBulkImportService $service)
    {
        $recentImports = MaidBulkImport::query()
            ->when(\App\Support\BranchQueryContext::activeBranchId(), fn ($query, $branchId) => $query->whereHas('uploadedBy', fn ($adminQuery) => $adminQuery->where('branch_id', $branchId)))
            ->when(!AdminGuardHasAnyPerm(['data_import_review', 'data_import_approve']), fn ($query) => $query->where('uploaded_by', auth('admin')->id()))
            ->with(['uploadedBy', 'approvedBy'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.ingaz.maids.bulk_import', [
            'columns' => $service->templateColumns(),
            'exampleRow' => $service->templateExampleRow(),
            'recentImports' => $recentImports,
        ]);
    }

    public function template(MaidBulkImportService $service)
    {
        return $service->createTemplateDownloadResponse();
    }

    public function store(BulkMaidImportUploadRequest $request, MaidBulkImportService $service)
    {
        $import = $service->createImportFromUpload($request->file('file'), auth('admin')->id() ?? auth()->id());

        return redirect()
            ->route('maids.bulk-import.show', $import)
            ->with('success', 'تم رفع الملف وتحليل البيانات بنجاح. راجع الصفوف قبل الاعتماد.');
    }

    public function show(MaidBulkImport $maidBulkImport, MaidBulkImportService $service)
    {
        $this->authorizeImport($maidBulkImport);
        // A fatal error can end a previous batch before its cached counters update.
        // Rebuild them from row statuses whenever the review screen is opened.
        $service->refreshImportCounters($maidBulkImport);
        $maidBulkImport->refresh();
        $maidBulkImport->load(['uploadedBy', 'approvedBy']);
        $rows = $maidBulkImport->rows()
            ->with('maid')
            ->paginate(100)
            ->withQueryString();

        return view('admin.ingaz.maids.bulk_import_review', [
            'import' => $maidBulkImport,
            'rows' => $rows,
        ]);
    }

    public function approve(MaidBulkImport $maidBulkImport, MaidBulkImportService $service, MaidAction $maidAction)
    {
        $this->authorizeImport($maidBulkImport);
        $result = \App\Support\DataImportBranchContext::run($maidBulkImport->uploadedBy?->branch_id,
            fn () => $service->approveImport($maidBulkImport, $maidAction, auth('admin')->id() ?? auth()->id()));

        return redirect()
            ->route('maids.bulk-import.show', [
                'maidBulkImport' => $maidBulkImport,
                'auto_continue' => ($result['remaining'] ?? 0) > 0 ? 1 : 0,
            ])
            ->with('success', "تمت معالجة دفعة: نجح {$result['imported']} وتعذر {$result['failed']}. المتبقي {$result['remaining']} صف.");
    }

    private function authorizeImport(MaidBulkImport $import): void
    {
        $branchId = \App\Support\BranchQueryContext::activeBranchId();
        abort_if($branchId && (int) $import->uploadedBy?->branch_id !== $branchId, 404);
        abort_unless((int) $import->uploaded_by === (int) auth('admin')->id()
            || AdminGuardHasAnyPerm(['data_import_review', 'data_import_approve']), 403);
    }
}
