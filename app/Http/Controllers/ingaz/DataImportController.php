<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Services\ingaz\DataImportExecutionService;
use App\Services\ingaz\DataImportTemplateService;
use App\Services\ingaz\FinanceBondGenerationService;
use App\Services\ingaz\MaidBulkImportService;
use App\Services\ingaz\ArabiaClientsImportService;
use App\Services\ingaz\AlrafedinTransferServicesImportService;
use App\Services\ingaz\LegacyActivityLogImportService;
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

        abort_if(!$template || ($template['downloadable'] ?? true) === false, 404);

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
        ArabiaClientsImportService $arabiaClientsImport,
        AlrafedinTransferServicesImportService $alrafedinTransferImport,
        LegacyActivityLogImportService $legacyActivityLogImport
    )
    {
        $availableTypes = array_keys($templates->templates());

        $data = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', $availableTypes)],
            'finance_constraints_missing_only' => ['nullable', 'boolean'],
            // Legacy Alrafedin .xls reports are HTML tables with an .xls extension.
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,html,txt', 'max:51200'],
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

        $result = match ($data['type']) {
            'legacy_activity_logs' => $legacyActivityLogImport->import($request->file('file'), $adminId),
            'arabia_clients' => $arabiaClientsImport->import($request->file('file'), $adminId),
            'alrafedin_transfer_services' => $alrafedinTransferImport->import($request->file('file'), $adminId),
            'finance_constraints' => $request->boolean('finance_constraints_missing_only')
                ? $importer->importMissingFinanceConstraints($request->file('file'), $adminId)
                : $importer->import($data['type'], $request->file('file'), $adminId),
            default => $importer->import($data['type'], $request->file('file'), $adminId),
        };
        $shownErrors = array_slice($result['errors'] ?? [], 0, 10);
        $message = "تمت معالجة {$result['total']} صف. جديد: {$result['created']}، محدث: {$result['updated']}، متجاوز: {$result['skipped']}.";
        if ($data['type'] === 'arabia_recruitment_orders') {
            $message .= " ملاحظات جديدة: " . ($result['notes_created'] ?? 0)
                . "، مراجع مالية: " . ($result['financial_references'] ?? 0)
                . "، إرساليات العقود المكتملة: " . ($result['completed_dispatches'] ?? 0)
                . "، عاملات تمت مصالحة توفرهن: " . ($result['reconciled_maids'] ?? 0)
                . "، وصفوف أصلية محفوظة: " . ($result['archived_rows'] ?? 0) . ".";
        }
        if ($data['type'] === 'alrafedin_transfer_services') {
            $message .= ' نقل خدمات سابق: ' . ($result['legacy_sponsorship'] ?? 0)
                . '، نقل خدمات: ' . ($result['transfer_service'] ?? 0) . '.';
        }
        if ($data['type'] === 'legacy_activity_logs') {
            $message .= ' يمكنك إعادة رفع الملف نفسه بأمان؛ ستُحدّث السجلات السابقة دون تكرار.';
        }
        if ($data['type'] === 'legacy_rental_orders') {
            $message .= ' عاملات مرتبطة بسير الإيجار: ' . ($result['rental_maids'] ?? 0)
                . '، عقود تشغيلية ومالية: ' . ($result['rental_contracts'] ?? 0)
                . '، فواتير تأجير: ' . ($result['rental_invoices'] ?? 0)
                . '، سندات قبض مرتبطة: ' . ($result['receipt_links'] ?? 0)
                . '، بانتظار استيراد سندات القبض: ' . ($result['pending_receipt_links'] ?? 0)
                . '، صفوف أصلية محفوظة: ' . ($result['archived_rows'] ?? 0)
                . '، مكتملة سابقًا دون تغيير: ' . ($result['unchanged_rows'] ?? 0) . '.';
            if ($result['active_snapshot'] ?? false) {
                $message .= ' تمت معاملة الملف كلقطة العقود السارية؛ عاملات مرتبطة بخارج الإيواء: '
                    . ($result['outside_shelter_links'] ?? 0)
                    . '، عقود سابقة أغلقت لغيابها من اللقطة: ' . ($result['deactivated_contracts'] ?? 0)
                    . '، دون عقد شركة مطابق: ' . ($result['missing_company_contracts'] ?? 0) . '.';
            }
        }
        if ($data['type'] === 'legacy_invoices') {
            $message .= ' تم الحفاظ على أرقام الفواتير وبيانات المصدر وحالة المشاركة السابقة مع هيئة الزكاة، وسُجلت الفواتير المستوردة كمسددة حسب اعتماد الإدارة.';
            $message .= ' ملفات PDF أصلية مرتبطة: ' . ($result['source_files_attached'] ?? 0) . '.';
        }
        if (in_array($data['type'], ['receipt_bonds', 'disbursement_bonds'], true)) {
            $message .= ' تم الحفاظ على رقم السند الأصلي، وإنشاء/تحديث القيد المحاسبي المرتبط، وأرشفة الصف الخام كاملًا مع اسم الملف ورقم الصف. كما تمت مزامنة روابط عقود الاستقدام والتأجير المتاحة.';
        }
        if ($data['type'] === 'finance_constraints' && ($result['accounts_created'] ?? 0) > 0) {
            $message .= ' حسابات مالية ناقصة تم إنشاؤها وربطها بالشجرة: '
                . $result['accounts_created'] . '.';
        }
        if (in_array($data['type'], ['finance_cost_centers', 'finance_constraints'], true)) {
            $message .= ' تفاصيل قيود رُبطت تلقائياً بمراكز طلبات الاستقدام: '
                . ($result['cost_center_links'] ?? 0) . '.';
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
