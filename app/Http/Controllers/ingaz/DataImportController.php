<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Services\ingaz\DataImportTemplateService;
use App\Services\ingaz\FinanceBondGenerationService;
use App\Services\ingaz\MaidBulkImportService;
use Illuminate\Http\Request;

class DataImportController extends Controller
{
    public function index(DataImportTemplateService $templates, FinanceBondGenerationService $bondGenerator)
    {
        return view('admin.ingaz.reports.import', [
            'templates' => $templates->templates(),
            'reviewBatches' => \App\Models\DataImportBatch::query()->when(!AdminGuardHasAnyPerm(['data_import_review', 'data_import_approve']), fn ($query) => $query->where('created_by', auth('admin')->id()))->latest()->limit(20)->get(),
            'recentUploads' => $templates->recentUploads(),
            'bondGenerationStats' => $bondGenerator->statistics(),
        ]);
    }

    public function template(string $type, DataImportTemplateService $templates)
    {
        $template = $templates->template($type);

        abort_if(!$template || ($template['downloadable'] ?? true) === false, 404);

        if (!empty($template['external_template_route'])) {
            return app(\App\Services\ingaz\MaidBulkImportService::class)->createTemplateDownloadResponse();
        }

        return $templates->createTemplateDownloadResponse($type);
    }

    public function store(
        Request $request,
        DataImportTemplateService $templates,
        MaidBulkImportService $maidImportService
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

        try {
            $batch = app(\App\Services\ingaz\DataImportReviewService::class)->stage(
                $data['type'], $request->file('file'), $adminId,
                ['finance_constraints_missing_only' => $request->boolean('finance_constraints_missing_only')]
            );
        } catch (\InvalidArgumentException | \PhpOffice\PhpSpreadsheet\Reader\Exception $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        $url = route('reports.import.show', $batch);
        if ($request->expectsJson()) return response()->json(['redirect' => $url]);
        return redirect($url)->with('success', 'تم رفع الملف وفحصه. لم تُحفظ بيانات تشغيلية بعد. راجع النتائج ثم اعتمد الصفوف الصحيحة.');
    }

    public function show(\App\Models\DataImportBatch $batch, Request $request, DataImportTemplateService $templates)
    {
        $this->authorizeBatch($batch);
        $status = $request->validate(['status' => 'nullable|in:valid,invalid,approved'])['status'] ?? null;
        return view('admin.ingaz.reports.import-review', [
            'batch' => $batch, 'template' => $templates->template($batch->type),
            'rows' => $batch->rows()->when($status, fn ($query) => $query->where('status', $status))->orderBy('id')->paginate(100)->withQueryString(),
            'counts' => $batch->rows()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status'),
            'wholeFile' => app(\App\Services\ingaz\DataImportReviewService::class)->requiresWholeFile($batch->type),
        ]);
    }

    public function approve(\App\Models\DataImportBatch $batch, \App\Services\ingaz\DataImportReviewService $review)
    {
        $this->authorizeBatch($batch);
        abort_unless(AdminGuardHasAnyPerm(['data_import_approve']), 403);
        if (in_array($batch->type, ['invoices', 'updated_invoices', 'legacy_invoices', 'legacy_rental_orders'], true)) {
            abort_unless(\App\Support\InvoiceAuthorization::canIssueDirectly() || \App\Support\InvoiceAuthorization::canApprove(), 403);
        }
        if ($batch->type === 'admins') {
            abort_unless(AdminGuardHasAnyPerm([\App\PermKeys::USERS_CREATE]), 403);
            abort_unless(AdminGuardHasAnyPerm([\App\PermKeys::PERMISSIONS_ASSIGN]), 403);
        }
        $result = $review->approve($batch, auth('admin')->id());
        $saved = (int) ($result['approved_count'] ?? 0);
        if (!empty($result['error_count']) && !$review->requiresWholeFile($batch->type)) {
            return redirect()->route('reports.import.show', $batch)->with($saved ? 'warning' : 'error',
                $saved ? "تم اعتماد {$saved} صف. بقي {$result['error_count']} صف به أخطاء؛ راجع السبب أمام كل صف وصححه ثم ارفعه منفرداً."
                    : 'لم يُعتمد أي صف. راجع سبب الخطأ أمام كل صف وصحح الصفوف ثم أعد رفعها.');
        }
        return redirect()->route('reports.import.show', $batch)->with(
            empty($result['error_count']) ? 'success' : 'error',
            empty($result['error_count']) ? 'تم اعتماد الاستيراد وحفظ الصفوف الصحيحة.' : 'تعذر الاعتماد. راجع الأخطاء الموضحة؛ لم تحفظ أي تغييرات تشغيلية في هذه المحاولة.'
        );
    }

    private function authorizeBatch(\App\Models\DataImportBatch $batch): void
    {
        // Upload-only users may inspect their own results; reviewing others requires review/approval permission.
        abort_unless((int) $batch->created_by === (int) auth('admin')->id()
            || AdminGuardHasAnyPerm(['data_import_review', 'data_import_approve']), 403);
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
