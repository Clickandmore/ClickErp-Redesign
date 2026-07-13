<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Services\ingaz\DataImportExecutionService;
use App\Services\ingaz\DataImportTemplateService;
use App\Services\ingaz\FinanceBondGenerationService;
use App\Services\ingaz\MaidBulkImportService;
use App\Services\ingaz\ArabiaClientsImportService;
use Illuminate\Http\Request;

class DataImportController extends Controller
{
    public function index(DataImportTemplateService $templates, FinanceBondGenerationService $bondGenerator)
    {
        return view('admin.ingaz.reports.import', [
            'templates' => $templates->templates(),
            'recentUploads' => $templates->recentUploads(),
            'bondGenerationStats' => $bondGenerator->statistics(),
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
        DataImportExecutionService $importer,
        ArabiaClientsImportService $arabiaClientsImport
    )
    {
        $availableTypes = array_keys($templates->templates());

        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', $availableTypes)],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'],
        ], [
            'type.required' => 'اختر نوع البيانات.',
            'type.in' => 'نوع البيانات المحدد غير مدعوم.',
            'file.required' => 'اختر ملف الاستيراد.',
            'file.mimes' => 'يجب أن يكون الملف بصيغة Excel أو CSV.',
            'file.max' => 'حجم الملف يجب ألا يتجاوز 50 ميجابايت.',
        ]);

        $adminId = auth('admin')->id() ?? auth()->id();

        if (in_array($data['type'], ['maids', 'arabia_cvs'], true)) {
            $import = $data['type'] === 'arabia_cvs'
                ? $maidImportService->createArabiaImportFromUpload($request->file('file'), $adminId)
                : $maidImportService->createImportFromUpload($request->file('file'), $adminId);

            if ($request->expectsJson()) {
                session()->flash('success', 'تم رفع ملف السير وتحليل البيانات بنجاح. راجع الصفوف قبل الاعتماد.');

                return response()->json([
                    'redirect' => route('maids.bulk-import.show', $import),
                ]);
            }

            return redirect()
                ->route('maids.bulk-import.show', $import)
                ->with('success', 'تم رفع ملف السير وتحليل البيانات بنجاح. راجع الصفوف قبل الاعتماد.');
        }

        $result = $data['type'] === 'arabia_clients'
            ? $arabiaClientsImport->import($request->file('file'), $adminId)
            : $importer->import($data['type'], $request->file('file'), $adminId);
        $shownErrors = array_slice($result['errors'] ?? [], 0, 10);
        $message = "تمت معالجة {$result['total']} صف. جديد: {$result['created']}، محدث: {$result['updated']}، متجاوز: {$result['skipped']}.";
        if ($data['type'] === 'arabia_recruitment_orders') {
            $message .= " ملاحظات جديدة: " . ($result['notes_created'] ?? 0)
                . "، مراجع مالية: " . ($result['financial_references'] ?? 0)
                . "، عاملات تمت مصالحة توفرهن: " . ($result['reconciled_maids'] ?? 0)
                . "، وصفوف أصلية محفوظة: " . ($result['archived_rows'] ?? 0) . ".";
        }
        if (($result['error_count'] ?? 0) > count($shownErrors)) {
            $message .= " تم عرض أول " . count($shownErrors) . " خطأ من إجمالي {$result['error_count']} خطأ.";
        }

        if ($request->expectsJson()) {
            session()->flash('success', $message);
            session()->flash('import_errors', $shownErrors);

            return response()->json([
                'redirect' => route('reports.import.index'),
            ]);
        }

        return redirect()
            ->route('reports.import.index')
            ->with('success', $message)
            ->with('import_errors', $shownErrors);
    }

    public function generateBonds(FinanceBondGenerationService $bondGenerator)
    {
        $result = $bondGenerator->generateFromDailyConstraints(auth('admin')->id() ?? auth()->id());

        $message = "تمت معالجة {$result['processed']} قيد. تم إنشاء {$result['created']} سند، وتم تجاوز {$result['skipped']} قيد.";

        return redirect()
            ->route('reports.import.index')
            ->with('success', $message)
            ->with('bond_generation_errors', $result['errors'] ?? []);
    }
}
