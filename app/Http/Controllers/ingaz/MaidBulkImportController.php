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

    public function show(MaidBulkImport $maidBulkImport)
    {
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
        $result = $service->approveImport($maidBulkImport, $maidAction, auth('admin')->id() ?? auth()->id());

        return redirect()
            ->route('maids.bulk-import.show', $maidBulkImport)
            ->with('success', "تم اعتماد {$result['imported']} صف/صفوف بنجاح، وتعذر استيراد {$result['failed']} صف/صفوف.");
    }
}
