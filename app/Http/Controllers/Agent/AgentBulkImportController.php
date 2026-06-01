<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\ingaz\AgentMaidBulkImport;
use App\Models\RecruitmentOffices;
use App\Services\ingaz\AgentBulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AgentBulkImportController extends Controller
{
    public function create()
    {
        $offices = $this->agentOffices();
        $recentImports = AgentMaidBulkImport::query()
            ->whereIn('office_id', $offices->pluck('id')->all())
            ->with('office')
            ->latest()
            ->take(10)
            ->get();

        return view('agent.maids.bulk_import', compact('offices', 'recentImports'));
    }

    public function store(Request $request, AgentBulkImportService $service)
    {
        $officeIds = $this->agentOfficeIds();

        $request->validate([
            'file'      => 'required|file|mimes:xlsx,xls,csv,docx,doc,pdf|max:10240',
            'office_id' => 'required|integer|in:' . $officeIds->implode(','),
        ], [
            'file.required'      => 'يرجى اختيار ملف.',
            'file.mimes'         => 'نوع الملف غير مدعوم. الأنواع المقبولة: Excel, Word, PDF.',
            'file.max'           => 'حجم الملف يتجاوز الحد المسموح (10 ميغابايت).',
            'office_id.required' => 'يرجى اختيار المكتب.',
            'office_id.in'       => 'المكتب المختار غير مخصص لحسابك.',
        ]);

        $agentId = Auth::guard('agent')->id()
            ?? (int) session('_agent_impersonate_office_id');

        $import = $service->createImportFromUpload(
            $request->file('file'),
            $agentId,
            (int) $request->office_id
        );

        return redirect()
            ->route('agent.maids.bulk-import.show', $import)
            ->with('success', 'تم رفع الملف وتحليل البيانات. راجع السجلات أدناه.');
    }

    public function show(AgentMaidBulkImport $agentMaidBulkImport)
    {
        $this->authorizeImport($agentMaidBulkImport);

        $agentMaidBulkImport->load(['rows', 'office', 'approvedBy']);

        return view('agent.maids.bulk_import_review', [
            'import' => $agentMaidBulkImport,
        ]);
    }

    public function download(AgentMaidBulkImport $agentMaidBulkImport)
    {
        $this->authorizeImport($agentMaidBulkImport);

        $path = $agentMaidBulkImport->uploaded_file_path;

        if (!$path || !Storage::exists($path)) {
            abort(404, 'الملف غير موجود.');
        }

        return Storage::download($path, $agentMaidBulkImport->original_filename);
    }

    public function template(Request $request, AgentBulkImportService $service)
    {
        $type = $request->input('type', 'excel');

        return match ($type) {
            'word' => $this->wordTemplate($service),
            default => $this->excelTemplate($service),
        };
    }

    private function excelTemplate(AgentBulkImportService $service): \Symfony\Component\HttpFoundation\Response
    {
        $columns     = $service->templateColumns();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('بيانات العمالة');

        // Header row
        foreach ($columns as $index => $column) {
            $col   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $label = $column['label'] . ($column['required'] ? ' *' : '');
            $sheet->setCellValue($col . '1', $label);
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);

        // Example row
        $example = [
            'name'             => 'FATIMA ALI HASSAN',
            'passport_num'     => 'A1234567',
            'nationality'      => 'إثيوبية',
            'religion'         => 'مسيحية',
            'age'              => '28',
            'birth_date'       => '1996-05-10',
            'experience_years' => '3',
            'job_titles'       => 'عاملة منزلية',
            'price'            => '12000',
            'additional_notes' => 'مثال – يمكن تعديل هذا الصف',
        ];

        foreach ($columns as $index => $column) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col . '2', $example[$column['key']] ?? '');
        }

        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '7A5200']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8E1']],
        ]);

        foreach (range(1, count($columns)) as $i) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setWidth(22);
        }

        $sheet->getComment('A1')->getText()->createTextRun('الحقول المميزة بـ (*) إلزامية.');

        $writer   = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'agent-excel-template');
        $writer->save($tempFile);

        return response()
            ->download($tempFile, 'نموذج-استيراد-العمالة.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function wordTemplate(AgentBulkImportService $service): \Symfony\Component\HttpFoundation\Response
    {
        $phpWord  = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop'   => 720,
            'marginBottom' => 720,
            'marginLeft'  => 900,
            'marginRight' => 900,
        ]);

        // Title
        $titleStyle = ['bold' => true, 'size' => 16, 'color' => '1F4E78'];
        $section->addText('نموذج استيراد العمالة', $titleStyle, ['alignment' => 'right']);
        $section->addText('يمكن إضافة أكثر من عاملة — افصل بين كل عاملة وأخرى بسطر فارغ', ['size' => 9, 'color' => '888888'], ['alignment' => 'right']);
        $section->addTextBreak(1);

        $labelStyle = ['bold' => true, 'size' => 11];
        $valueStyle = ['size' => 11];

        // Helper: add a field row
        $addField = function (string $label, string $example, bool $required = false) use ($section, $labelStyle, $valueStyle) {
            $run = $section->addTextRun(['alignment' => 'right']);
            $run->addText($label . ($required ? ' *' : '') . ': ', $labelStyle);
            $run->addText($example, $valueStyle);
        };

        // Worker 1 — example
        $section->addText('[ عاملة ١ - مثال للتعديل ]', ['bold' => true, 'color' => '7A5200', 'size' => 10], ['alignment' => 'right']);
        $addField('الاسم',           'FATIMA ALI HASSAN',  true);
        $addField('رقم الجواز',      'A1234567',           true);
        $addField('الجنسية',         'إثيوبية',            true);
        $addField('الديانة',         'مسيحية');
        $addField('العمر',           '28');
        $addField('تاريخ الميلاد',  '1996-05-10');
        $addField('سنوات الخبرة',   '3');
        $addField('المهنة',          'عاملة منزلية');
        $addField('التكلفة',         '12000');
        $addField('ملاحظات',         '');
        $section->addTextBreak(1);

        // Separator
        $section->addText('─────────────────────────────────', ['color' => 'CCCCCC', 'size' => 9], ['alignment' => 'right']);
        $section->addTextBreak(1);

        // Worker 2 — blank
        $section->addText('[ عاملة ٢ ]', ['bold' => true, 'color' => '555555', 'size' => 10], ['alignment' => 'right']);
        $addField('الاسم',          '');
        $addField('رقم الجواز',     '');
        $addField('الجنسية',        '');
        $addField('الديانة',        '');
        $addField('العمر',          '');
        $addField('تاريخ الميلاد', '');
        $addField('سنوات الخبرة',  '');
        $addField('المهنة',         '');
        $addField('التكلفة',        '');
        $addField('ملاحظات',        '');
        $section->addTextBreak(1);

        // Footer note
        $section->addText('─────────────────────────────────', ['color' => 'CCCCCC', 'size' => 9], ['alignment' => 'right']);
        $section->addText('* الحقول الإلزامية: الاسم، رقم الجواز، الجنسية', ['size' => 9, 'color' => 'AA0000', 'italic' => true], ['alignment' => 'right']);
        $section->addText('يمكن إضافة عدد غير محدود من العمالة بنفس التنسيق أعلاه', ['size' => 9, 'color' => '666666', 'italic' => true], ['alignment' => 'right']);

        $writer   = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'agent-word-template') . '.docx';
        $writer->save($tempFile);

        return response()
            ->download($tempFile, 'نموذج-استيراد-العمالة.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }

    // -------------------------------------------------------------------------

    private function authorizeImport(AgentMaidBulkImport $import): void
    {
        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains($import->office_id)) {
            abort(403);
        }
    }

    private function agentOfficeIds(): \Illuminate\Support\Collection
    {
        $impersonated = session('_agent_impersonate_office_id');
        if ($impersonated && Auth::guard('admin')->check()) {
            return collect([(int) $impersonated]);
        }

        return Auth::guard('agent')
            ->user()
            ->agentOfficeLinks()
            ->pluck('office_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function agentOffices(): \Illuminate\Support\Collection
    {
        return RecruitmentOffices::whereIn('id', $this->agentOfficeIds()->all())->get();
    }
}
