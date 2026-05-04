<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Services\ingaz\DataImportExecutionService;
use App\Services\ingaz\DataImportTemplateService;
use App\Services\ingaz\MaidBulkImportService;
use Illuminate\Http\Request;

class DataImportController extends Controller
{
    public function index(DataImportTemplateService $templates)
    {
        return view('admin.ingaz.reports.import', [
            'templates' => $templates->templates(),
            'recentUploads' => $templates->recentUploads(),
        ]);
    }

    public function template(string $type, DataImportTemplateService $templates)
    {
        $template = $templates->template($type);

        abort_if(!$template, 404);

        if (!empty($template['external_template_route'])) {
            return redirect()->route($template['external_template_route']);
        }

        return $templates->createTemplateDownloadResponse($type);
    }

    public function store(
        Request $request,
        DataImportTemplateService $templates,
        MaidBulkImportService $maidImportService,
        DataImportExecutionService $importer
    )
    {
        $availableTypes = array_keys($templates->templates());

        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', $availableTypes)],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ], [
            'type.required' => 'اختر نوع البيانات.',
            'type.in' => 'نوع البيانات المحدد غير مدعوم.',
            'file.required' => 'اختر ملف الاستيراد.',
            'file.mimes' => 'يجب أن يكون الملف بصيغة Excel أو CSV.',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 20 ميجابايت.',
        ]);

        $adminId = auth('admin')->id() ?? auth()->id();

        if ($data['type'] === 'maids') {
            $import = $maidImportService->createImportFromUpload($request->file('file'), $adminId);

            return redirect()
                ->route('maids.bulk-import.show', $import)
                ->with('success', 'تم رفع ملف السير وتحليل البيانات بنجاح. راجع الصفوف قبل الاعتماد.');
        }

        $result = $importer->import($data['type'], $request->file('file'), $adminId);
        $message = "تمت معالجة {$result['total']} صف. جديد: {$result['created']}، محدث: {$result['updated']}، متجاوز: {$result['skipped']}.";

        return redirect()
            ->route('reports.import.index')
            ->with('success', $message)
            ->with('import_errors', array_slice($result['errors'], 0, 10));
    }
}
