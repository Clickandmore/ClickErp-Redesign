<?php

namespace App\Services\ingaz;

use App\Http\Actions\ingaz\MaidAction;
use App\Models\RecruitmentOffices;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidBulkImport;
use App\Models\ingaz\MaidBulkImportRow;
use App\Models\ingaz\MaidExternalReference;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\ingaz\Skill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaidBulkImportService
{
    private const APPROVAL_BATCH_SIZE = 20;
    private const DATA_SHEET_NAME = 'البيانات';
    private const LOOKUPS_SHEET_NAME = 'القوائم';
    private const INSTRUCTIONS_SHEET_NAME = 'تعليمات';
    private const TEMPLATE_ROW_LIMIT = 10000;
    private const INSERT_CHUNK_SIZE = 500;
    private const TEMPLATE_EXAMPLE_PASSPORT = 'TEMPLATE-EXAMPLE';
    private const TEMPLATE_EXAMPLE_NAME = 'EDIT_ME_NAME';

    private const DEFAULT_DURATION = '24 شهر';

    private const LANGUAGE_ALIASES = [
        'arabic' => ['arabic', 'العربية', 'عربي'],
        'english' => ['english', 'الإنجليزية', 'انجليزي', 'الانجليزية'],
        'amharic' => ['amharic', 'الأمهرية', 'امهرية', 'الامهرية'],
        'mother' => ['mother', 'اللغة الأم', 'اللغه الام', 'اللغة الام', 'الام'],
    ];

    private const REQUEST_TYPE_ALIASES = [
        Maid::REQUEST_TYPE_KNOWN => ['known', 'معروفة', 'معروف'],
        Maid::REQUEST_TYPE_SPECIFIC => ['specific', 'محددة', 'محدد'],
        Maid::REQUEST_TYPE_CUSTOM => ['custom_spec', 'custom', 'حسب المواصفات', 'مواصفات خاصة', 'حسب الطلب'],
    ];

    private const EXPERIENCE_STATUS_ALIASES = [
        'has_experience' => ['has_experience', 'سبق لها العمل', 'لديها خبرة', 'خبرة'],
        'no_experience' => ['no_experience', 'لم يسبق لها العمل', 'بدون خبرة', 'لا توجد خبرة'],
    ];

    private const MAID_STATUS_ALIASES = [
        'available' => ['available', 'المتاحة', 'متاحة', 'متاح'],
        'reserved' => ['reserved', 'المحجوزة', 'محجوزة', 'محجوز'],
        'contracted' => ['contracted', 'تم التعاقد', 'متعاقدة', 'متعاقد'],
        'in_accommodation' => ['in_accommodation', 'داخل الايواء', 'داخل الإيواء', 'إيواء', 'ايواء'],
        'backout' => ['backout', 'باك اوت', 'باك أوت'],
        'unavailable' => ['unavailable', 'غير متاحة', 'غير متاح', 'not available'],
        'completed' => ['completed', 'مكتملة', 'مكتمل'],
    ];

    private const MARITAL_STATUS_MAP = [
        'عازبة' => 'single',
        'أعزب' => 'single',
        'متزوجة' => 'married',
        'متزوج' => 'married',
    ];

    public function __construct(protected RentalMaidFinanceService $rentalMaidFinanceService)
    {
    }

    public function templateColumns(): array
    {
        return [
            ['key' => 'name:en', 'label' => 'الاسم بالإنجليزية', 'required' => true],
            ['key' => 'passport_num', 'label' => 'رقم الجواز', 'required' => true],
            ['key' => 'nationality', 'label' => 'الجنسية', 'required' => true],
            ['key' => 'office', 'label' => 'المكتب الخارجي', 'required' => false],
            ['key' => 'religion', 'label' => 'الديانة', 'required' => false],
            ['key' => 'type_id', 'label' => 'نوع السيرة', 'required' => true],
            ['key' => 'job_titles', 'label' => 'المهنة', 'required' => true],
            ['key' => 'arrival_date', 'label' => 'تاريخ الوصول', 'required' => false],
            ['key' => 'experience_status', 'label' => 'حالة الخبرة', 'required' => true],
            ['key' => 'experience_years', 'label' => 'سنوات الخبرة', 'required' => false],
            ['key' => 'salary', 'label' => 'الراتب', 'required' => false],
            ['key' => 'price', 'label' => 'التكلفة', 'required' => false],
            ['key' => 'birth_date', 'label' => 'تاريخ الميلاد', 'required' => false],
            ['key' => 'age', 'label' => 'العمر', 'required' => false],
            ['key' => 'height', 'label' => 'الطول', 'required' => false],
            ['key' => 'weight', 'label' => 'الوزن', 'required' => false],
            ['key' => 'id_number', 'label' => 'رقم الهوية', 'required' => false],
            ['key' => 'id_expiry_date', 'label' => 'تاريخ انتهاء الهوية', 'required' => false],
            ['key' => 'marital_status:ar', 'label' => 'الحالة الاجتماعية', 'required' => false],
            ['key' => 'childrens_count', 'label' => 'عدد الأطفال', 'required' => false],
            ['key' => 'duration', 'label' => 'مدة العقد', 'required' => false],
            ['key' => 'request_type', 'label' => 'نوع الطلب', 'required' => false],
            ['key' => 'skills', 'label' => 'المهارات', 'required' => false],
            ['key' => 'languages', 'label' => 'اللغات', 'required' => false],
            ['key' => 'language_percentages', 'label' => 'نسب اللغات', 'required' => false],
            ['key' => 'additional_notes', 'label' => 'ملاحظات إضافية', 'required' => false],
            ['key' => 'create_shelter_contract', 'label' => 'إنشاء عقد إيواء', 'required' => false],
            ['key' => 'is_show', 'label' => 'إظهار في الموقع', 'required' => false],
            ['key' => 'video_link', 'label' => 'رابط فيديو', 'required' => false],
            ['key' => 'available', 'label' => 'available', 'required' => false],
            ['key' => 'status', 'label' => 'الحالة', 'required' => false],
            ['key' => 'id', 'label' => 'ID', 'required' => false],
            ['key' => 'added_by', 'label' => 'أضيفت بواسطة', 'required' => false],
            ['key' => 'created_at', 'label' => 'أنشئ بتاريخ', 'required' => false],
            ['key' => 'gender', 'label' => 'الجنس', 'required' => false],
        ];
    }

    public function createTemplateDownloadResponse()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $dataSheet = new Worksheet($spreadsheet, self::DATA_SHEET_NAME);
        $instructionsSheet = new Worksheet($spreadsheet, self::INSTRUCTIONS_SHEET_NAME);
        $lookupSheet = new Worksheet($spreadsheet, self::LOOKUPS_SHEET_NAME);

        $spreadsheet->addSheet($instructionsSheet);
        $spreadsheet->addSheet($dataSheet);
        $spreadsheet->addSheet($lookupSheet);
        $spreadsheet->setActiveSheetIndexByName(self::DATA_SHEET_NAME);

        $lookups = $this->lookupCollections();

        $this->buildInstructionsSheet($instructionsSheet);
        $this->buildLookupSheet($lookupSheet, $lookups);
        $this->buildDataSheet($dataSheet, $lookups);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'maid-import-template');
        $writer->save($tempFile);

        return response()
            ->download(
                $tempFile,
                'maids-bulk-import-template.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    public function templateExampleRow(): array
    {
        $nationality = Nationality::query()->orderBy('id')->first();
        $office = RecruitmentOffices::query()
            ->when($nationality?->id, fn ($query) => $query->where('nationality_id', $nationality->id))
            ->orderBy('id')
            ->first();

        if (!$office) {
            $office = RecruitmentOffices::query()->orderBy('id')->first();
        }

        $religion = Religion::query()->orderBy('id')->first();
        $jobType = JobType::query()->orderBy('id')->first();
        $jobTitles = JobTitle::query()->orderBy('id')->take(2)->get();
        $skills = Skill::query()->orderBy('id')->take(2)->get();
        $requestTypes = array_values(Maid::getRequestTypeOptions());

        return [
            'name:en' => self::TEMPLATE_EXAMPLE_NAME,
            'passport_num' => self::TEMPLATE_EXAMPLE_PASSPORT,
            'nationality' => $nationality?->sub_title ?: $nationality?->title ?: '',
            'office' => $office?->title ?: '',
            'religion' => $religion?->title ?: '',
            'type_id' => $jobType?->title ?: '',
            'job_titles' => $jobTitles->pluck('title')->filter()->implode('|'),
            'arrival_date' => now()->addDays(14)->format('Y-m-d'),
            'experience_status' => 'سبق لها العمل',
            'experience_years' => '2',
            'salary' => $nationality?->monthly_salary !== null ? (string) $nationality->monthly_salary : '1500',
            'price' => $nationality?->price !== null ? (string) $nationality->price : '12000',
            'birth_date' => '1998-01-01',
            'age' => '27',
            'height' => '160',
            'weight' => '60',
            'id_number' => 'EDIT-ID-001',
            'id_expiry_date' => now()->addYear()->format('Y-m-d'),
            'marital_status:ar' => 'عازبة',
            'childrens_count' => '0',
            'duration' => self::DEFAULT_DURATION,
            'request_type' => $requestTypes[0] ?? '',
            'skills' => $skills->pluck('title')->filter()->implode('|'),
            'languages' => 'arabic|english',
            'language_percentages' => 'arabic:90|english:70',
            'additional_notes' => 'مثال توضيحي: عدل هذا الصف أو انسخه ثم غير قيمته قبل الرفع.',
            'create_shelter_contract' => 'لا',
            'is_show' => 'نعم',
            'video_link' => '',
            'available' => 'نعم',
            'status' => 'متاحة',
            'id' => '',
            'added_by' => '',
            'created_at' => '',
            'gender' => 'أنثى',
        ];
    }

    public function createImportFromUpload(UploadedFile $file, int $adminId): MaidBulkImport
    {
        $this->prepareLargeImportRuntime();

        $storedPath = $file->storeAs(
            'maid-imports',
            now()->format('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $file->getClientOriginalName())
        );

        $import = MaidBulkImport::create([
            'uploaded_by' => $adminId,
            'status' => 'processing',
            'original_filename' => $file->getClientOriginalName(),
            'uploaded_file_path' => $storedPath,
        ]);

        try {
            $rows = $this->parseUploadedFile(Storage::path($storedPath));
            $this->persistImportRows($import, $rows);
        } catch (\Throwable $exception) {
            $import->update([
                'status' => 'failed',
                'meta' => ['exception' => $exception->getMessage()],
            ]);

            throw $exception;
        }

        return $import->fresh('rows');
    }

    public function createArabiaImportFromUpload(UploadedFile $file, int $adminId): MaidBulkImport
    {
        $this->prepareLargeImportRuntime();

        $storedPath = $file->storeAs(
            'maid-imports',
            now()->format('Ymd_His') . '_arabia_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $file->getClientOriginalName())
        );

        $import = MaidBulkImport::create([
            'uploaded_by' => $adminId,
            'status' => 'processing',
            'original_filename' => $file->getClientOriginalName(),
            'uploaded_file_path' => $storedPath,
            'meta' => ['source' => 'arabia'],
        ]);

        try {
            $this->persistImportRows($import, $this->parseArabiaFile(Storage::path($storedPath)));
        } catch (\Throwable $exception) {
            $import->update(['status' => 'failed', 'meta' => [
                'source' => 'arabia',
                'exception' => $exception->getMessage(),
            ]]);
            throw $exception;
        }

        return $import->fresh('rows');
    }

    public function approveImport(MaidBulkImport $import, MaidAction $maidAction, int $adminId): array
    {
        $this->prepareLargeImportRuntime();

        $rowsQuery = MaidBulkImportRow::query()
            ->where('maid_bulk_import_id', $import->id)
            ->where('status', 'pending');

        if (!$rowsQuery->exists()) {
            return [
                'imported' => 0,
                'failed' => 0,
            ];
        }

        $import->update([
            'status' => 'processing',
            'approved_by' => $adminId,
            'approved_at' => now(),
        ]);

        $importedCount = 0;
        $failedCount = 0;

        $rows = MaidBulkImportRow::query()
            ->where('maid_bulk_import_id', $import->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit(self::APPROVAL_BATCH_SIZE)
            ->get();

        foreach ($rows as $row) {
                    $payload = $row->normalized_data ?? [];

                    try {
                        if (empty($payload)) {
                            throw ValidationException::withMessages([
                                'row' => ['لا توجد بيانات جاهزة للاستيراد في هذا الصف.'],
                            ]);
                        }

                        $request = Request::create('', 'POST', $payload);
                        $request->setUserResolver(function () {
                            return auth('admin')->user();
                        });

                        DB::transaction(function () use ($maidAction, $payload, $request, $row, &$importedCount, $adminId) {
                            $data = $payload;
                            $data = $this->materializeArabiaMedia($data);
                            $downloadedMedia = array_filter([
                                'image' => $data['image'] ?? null,
                                'cv_file' => $data['cv_file'] ?? null,
                            ]);
                            $data['status'] = $data['status'] ?? 'available';
                            $data['available'] = $data['available'] ?? ($data['status'] === 'available' || $data['status'] === 'completed' ? '1' : '0');
                            $data['added_by'] = $data['added_by'] ?? $adminId;
                            $data['preserve_null_arrival_date'] = true;

                            if (!empty($data['arabia_worker_number'])) {
                                $maid = MaidExternalReference::query()
                                    ->where('source', 'arabia')
                                    ->where('external_id', (string) $data['arabia_worker_number'])
                                    ->first()?->maid;

                                $maid ??= Maid::where('arabia_worker_number', $data['arabia_worker_number'])->first();
                                $maid ??= Maid::whereRaw('UPPER(TRIM(passport_num)) = ?', [mb_strtoupper(trim($data['passport_num']))])->first();
                            } else {
                                $maid = Maid::where('passport_num', $data['passport_num'])->first();
                            }

                            if (!$maid && !empty($data['id'])) {
                                $maid = Maid::find((int) $data['id']);
                            }

                            if ($maid) {
                                // updateMaid expects browser UploadedFile objects for media. Arabia
                                // assets have already been saved locally, so persist their paths after it.
                                unset($data['image'], $data['cv_file']);
                                unset(
                                    $data['preserve_null_arrival_date'],
                                    $data['job_id'],
                                    $data['create_shelter_contract'],
                                    $data['rental_finance_items'],
                                    $data['rental_finance_items_confirmed']
                                );
                                $maidAction->updateMaid($maid->id, $data, $request);
                                $maid = $maid->fresh();
                                if ($downloadedMedia !== []) {
                                    $maid->update($downloadedMedia);
                                }
                            } else {
                                $maid = $maidAction->storeMaid($data, $request);
                            }

                            if (!empty($data['arabia_worker_number'])) {
                                if (!$maid->arabia_worker_number) {
                                    $maid->update(['arabia_worker_number' => (int) $data['arabia_worker_number']]);
                                }
                                MaidExternalReference::updateOrCreate(
                                    ['source' => 'arabia', 'external_id' => (string) $data['arabia_worker_number']],
                                    ['maid_id' => $maid->id]
                                );
                            }

                            $row->update([
                                'status' => 'imported',
                                'maid_id' => $maid->id,
                                'imported_at' => now(),
                                'error_messages' => [],
                            ]);

                            $importedCount++;
                        });
                    } catch (\Throwable $exception) {
                        $failedCount++;
                        $row->update([
                            'status' => 'failed',
                            'error_messages' => $this->extractErrors($exception),
                        ]);
                    }

                // Release large downloaded response/model graphs before the next row.
                unset($payload, $request, $row);
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
        }

        $this->refreshImportCounters($import->fresh());

        $remaining = MaidBulkImportRow::query()
            ->where('maid_bulk_import_id', $import->id)
            ->where('status', 'pending')
            ->count();

        return [
            'imported' => $importedCount,
            'failed' => $failedCount,
            'remaining' => $remaining,
            'batch_size' => self::APPROVAL_BATCH_SIZE,
        ];
    }

    private function buildInstructionsSheet(Worksheet $sheet): void
    {
        $sheet->setRightToLeft(true);
        $sheet->setCellValue('A1', 'تعليمات الاستيراد الجماعي');
        $sheet->setCellValue('A3', '1. استخدم ورقة "البيانات" فقط لإدخال السجلات.');
        $sheet->setCellValue('A4', '2. الحقول التي لها قوائم منسدلة يجب اختيارها من القيم المتاحة.');
        $sheet->setCellValue('A5', '3. الحقول متعددة القيم مثل المهنة أو المهارات تُكتب مفصولة بعلامة |');
        $sheet->setCellValue('A6', '4. نسب اللغات تُكتب بهذا الشكل: arabic:90|english:70');
        $sheet->setCellValue('A7', '5. الصور وملفات السيرة غير مدعومة في الاستيراد الجماعي حالياً ويمكن إضافتها لاحقاً.');
        $sheet->setCellValue('A8', '6. الصف رقم 2 داخل ورقة "البيانات" هو مثال جاهز للتعديل أو النسخ.');
        $sheet->setCellValue('A9', '7. إذا بقي رقم الجواز TEMPLATE-EXAMPLE كما هو، فسيتجاهله النظام تلقائياً عند الرفع.');
        $sheet->setCellValue('A10', '8. بعد الرفع ستظهر شاشة مراجعة قبل الاعتماد النهائي.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getColumnDimension('A')->setWidth(120);
    }

    private function buildLookupSheet(Worksheet $sheet, array $lookups): void
    {
        $sheet->setRightToLeft(true);

        $columns = [
            'A' => ['title' => 'الجنسيات', 'values' => $lookups['nationalities']->pluck('label')->all()],
            'B' => ['title' => 'المكاتب الخارجية', 'values' => $lookups['offices']->pluck('label')->all()],
            'C' => ['title' => 'الديانات', 'values' => $lookups['religions']->pluck('label')->all()],
            'D' => ['title' => 'أنواع السيرة', 'values' => $lookups['job_types']->pluck('label')->all()],
            'E' => ['title' => 'المهن', 'values' => $lookups['job_titles']->pluck('label')->all()],
            'F' => ['title' => 'المهارات', 'values' => $lookups['skills']->pluck('label')->all()],
            'G' => ['title' => 'نوع الطلب', 'values' => array_values(Maid::getRequestTypeOptions())],
            'H' => ['title' => 'حالة الخبرة', 'values' => ['سبق لها العمل', 'لم يسبق لها العمل']],
            'I' => ['title' => 'الحالة الاجتماعية', 'values' => array_keys(self::MARITAL_STATUS_MAP)],
            'J' => ['title' => 'قيم نعم/لا', 'values' => ['نعم', 'لا']],
            'K' => ['title' => 'اللغات المعتمدة', 'values' => ['arabic', 'english', 'amharic', 'mother']],
            'L' => ['title' => 'حالات السير الذاتية', 'values' => $this->maidStatusLabels()],
        ];

        foreach ($columns as $column => $meta) {
            $sheet->setCellValue($column . '1', $meta['title']);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);

            foreach ($meta['values'] as $index => $value) {
                $sheet->setCellValue($column . ($index + 2), $value);
            }

            $sheet->getColumnDimension($column)->setWidth(30);
        }
    }

    private function buildDataSheet(Worksheet $sheet, array $lookups): void
    {
        $sheet->setRightToLeft(true);
        $columns = $this->templateColumns();

        foreach ($columns as $index => $column) {
            $cell = Coordinate::stringFromColumnIndex($index + 1) . '1';
            $label = $column['label'] . ($column['required'] ? ' *' : '');
            $sheet->setCellValue($cell, $label);
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
        ]);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");

        foreach (range(1, count($columns)) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(24);
        }

        $exampleRow = $this->templateExampleRow();
        foreach ($columns as $index => $column) {
            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($index + 1) . '2',
                $exampleRow[$column['key']] ?? ''
            );
        }

        $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '7A5200'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF8E1'],
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getComment('A2')->getText()->createTextRun('هذا صف مثال. عدله أو انسخه. إذا بقي رقم الجواز TEMPLATE-EXAMPLE فسيتجاهله النظام تلقائياً.');

        $validations = [
            'C' => $this->rangeFormula('A', $lookups['nationalities']->count()),
            'D' => $this->rangeFormula('B', $lookups['offices']->count()),
            'E' => $this->rangeFormula('C', $lookups['religions']->count()),
            'F' => $this->rangeFormula('D', $lookups['job_types']->count()),
            'I' => $this->rangeFormula('H', 2),
            'S' => $this->rangeFormula('I', count(self::MARITAL_STATUS_MAP)),
            'V' => $this->rangeFormula('G', count(Maid::getRequestTypeOptions())),
            'AA' => $this->rangeFormula('J', 2),
            'AB' => $this->rangeFormula('J', 2),
            'AD' => $this->rangeFormula('J', 2),
            'AE' => $this->rangeFormula('L', count($this->maidStatusLabels())),
        ];

        foreach ($validations as $column => $formula) {
            $this->applyListValidation($sheet, $column, $formula);
        }

        $sheet->getComment('G1')->getText()->createTextRun('يمكن إدخال أكثر من مهنة مفصولة بعلامة |');
        $sheet->getComment('W1')->getText()->createTextRun('يمكن إدخال أكثر من مهارة مفصولة بعلامة |');
        $sheet->getComment('X1')->getText()->createTextRun('القيم المعتمدة: arabic | english | amharic | mother');
        $sheet->getComment('Y1')->getText()->createTextRun('مثال: arabic:90|english:70');
        $sheet->getComment('AD1')->getText()->createTextRun('اختياري. اتركه فارغاً ليتم اعتباره نعم.');
        $sheet->getComment('AE1')->getText()->createTextRun('اختياري. اتركه فارغاً ليتم اعتباره متاحة.');
    }

    private function rangeFormula(string $column, int $count): string
    {
        $lastRow = max($count + 1, 2);

        return sprintf("'%s'!\$%s\$2:\$%s\$%d", self::LOOKUPS_SHEET_NAME, $column, $column, $lastRow);
    }

    private function applyListValidation(Worksheet $sheet, string $column, string $formula): void
    {
        for ($row = 2; $row <= self::TEMPLATE_ROW_LIMIT; $row++) {
            $validation = $sheet->getCell($column . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('قيمة غير صحيحة');
            $validation->setError('اختر قيمة من القائمة المتاحة.');
            $validation->setFormula1($formula);
        }
    }

    private function lookupCollections(): array
    {
        return [
            'nationalities' => Nationality::query()->orderBy('id')->get()->map(function (Nationality $item) {
                return ['id' => $item->id, 'label' => $item->sub_title ?: $item->title ?: ''];
            })->filter(fn ($item) => $item['label'] !== '')->values(),
            'offices' => RecruitmentOffices::query()->orderBy('id')->get()->map(function (RecruitmentOffices $item) {
                return ['id' => $item->id, 'label' => $item->title ?: ''];
            })->filter(fn ($item) => $item['label'] !== '')->values(),
            'religions' => Religion::query()->orderBy('id')->get()->map(function (Religion $item) {
                return ['id' => $item->id, 'label' => $item->title ?: ''];
            })->filter(fn ($item) => $item['label'] !== '')->values(),
            'job_types' => JobType::query()->orderBy('id')->get()->map(function (JobType $item) {
                return ['id' => $item->id, 'label' => $item->title ?: ''];
            })->filter(fn ($item) => $item['label'] !== '')->values(),
            'job_titles' => JobTitle::query()->orderBy('id')->get()->map(function (JobTitle $item) {
                return ['id' => $item->id, 'label' => $item->title ?: ''];
            })->filter(fn ($item) => $item['label'] !== '')->values(),
            'skills' => Skill::query()->orderBy('id')->get()->map(function (Skill $item) {
                return ['id' => $item->id, 'label' => $item->title ?: ''];
            })->filter(fn ($item) => $item['label'] !== '')->values(),
        ];
    }

    private function maidStatusLabels(): array
    {
        return [
            'متاحة',
            'محجوزة',
            'تم التعاقد',
            'داخل الإيواء',
            'باك أوت',
            'غير متاحة',
            'مكتملة',
        ];
    }

    private function parseUploadedFile(string $path): array
    {
        $spreadsheet = $this->loadSpreadsheetForImport($path);
        $sheet = $spreadsheet->getSheetByName(self::DATA_SHEET_NAME) ?: $spreadsheet->getSheet(0);

        $headers = $this->extractHeaders($sheet);

        if (!$this->headersMatchTemplate($headers)) {
            throw ValidationException::withMessages([
                'file' => ['هيكل التمبلت غير مطابق. يرجى تحميل التمبلت الجديد من النظام وإعادة تعبئته.'],
            ]);
        }

        $highestRow = $sheet->getHighestDataRow();
        $entityLookups = $this->lookupEntities();
        $rows = [];

        for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
            $rawRow = [];

            foreach ($this->templateColumns() as $columnIndex => $column) {
                $cellCoordinate = Coordinate::stringFromColumnIndex($columnIndex + 1) . $rowIndex;
                $rawRow[$column['key']] = $this->cellValue($sheet->getCell($cellCoordinate)->getValue());
            }

            if ($this->rowIsEmpty($rawRow)) {
                continue;
            }

            if ($this->isTemplateExampleRow($rawRow)) {
                continue;
            }

            $normalized = $this->normalizeRow($rawRow, $entityLookups);
            $rows[] = [
                'row_number' => $rowIndex,
                'raw_data' => $rawRow,
                'normalized_data' => $normalized['data'],
                'error_messages' => $normalized['errors'],
                'status' => empty($normalized['errors']) ? 'pending' : 'invalid',
            ];
        }

        return $rows;
    }

    private function parseArabiaFile(string $path): array
    {
        $spreadsheet = $this->loadSpreadsheetForImport($path);
        $sheet = $spreadsheet->getSheet(0);
        $headers = [];
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        for ($column = 1; $column <= $highestColumn; $column++) {
            $label = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column) . '1')->getValue());
            if ($label !== '') {
                $headers[$label] = $column;
            }
        }

        foreach (['رقم العامل', 'الاسم', 'رقم الجواز', 'الدولة'] as $requiredHeader) {
            if (!isset($headers[$requiredHeader])) {
                throw ValidationException::withMessages([
                    'file' => ["تنسيق ملف أربيا غير مطابق: العمود «{$requiredHeader}» غير موجود."],
                ]);
            }
        }

        $filename = strtolower(basename($path));
        $defaultProfession = str_contains($filename, 'private_driver')
            ? 'سائق خاص'
            : (str_contains($filename, 'general_worker')
                ? 'عامل عادي'
                : (str_contains($filename, 'home_farmer') ? 'مزارع منزلي' : null));

        $lookups = $this->lookupEntities();
        $defaultJobType = JobType::query()->orderBy('id')->first();
        if (!$defaultJobType) {
            throw ValidationException::withMessages(['file' => ['لا يوجد نوع سيرة مسجل في النظام.']]);
        }

        $rows = [];
        for ($rowIndex = 2; $rowIndex <= $sheet->getHighestDataRow(); $rowIndex++) {
            $value = function (string $label) use ($headers, $sheet, $rowIndex) {
                if (!isset($headers[$label])) {
                    return null;
                }
                return $this->cellValue($sheet->getCell(Coordinate::stringFromColumnIndex($headers[$label]) . $rowIndex)->getValue());
            };

            $workerNumber = $value('رقم العامل');
            $name = $value('الاسم');
            $passport = strtoupper(trim((string) $value('رقم الجواز'), " .\t\n\r\0\x0B"));
            if (!$workerNumber && !$name && !$passport) {
                continue;
            }

            $office = $value('المكتب');
            $profession = $value('المهنة') ?: $defaultProfession;
            if ($profession === 'اخصائي علاج طبيعي') {
                $profession = 'علاج طبيعي';
            }
            $marital = str_contains((string) $value('الحالة الاجتماعية'), 'متزوج') ? 'متزوجة' : 'عازبة';
            $raw = [
                'name:en' => $name,
                'passport_num' => $passport,
                'nationality' => $value('الدولة'),
                'office' => $office,
                'religion' => $value('الديانة'),
                'type_id' => $defaultJobType->id,
                'job_titles' => $profession,
                'experience_status' => 'لم يسبق لها العمل',
                'marital_status:ar' => $marital,
                'status' => 'متاحة',
                'available' => 'نعم',
                'is_show' => 'نعم',
                'gender' => 'أنثى',
                'additional_notes' => 'مستوردة من نظام أربيا. المكتب في المصدر: ' . ($office ?: '-'),
                'arabia_worker_number' => $workerNumber,
                'arabia_image_url' => $value('رابط الصورة'),
                'arabia_pdf_url' => $value('رابط ملف السيرة (PDF)'),
            ];

            $normalized = $this->normalizeRow($raw, $lookups);
            $normalized['data']['arabia_worker_number'] = $this->normalizeInteger($workerNumber);
            $normalized['data']['arabia_image_url'] = $value('رابط الصورة');
            $normalized['data']['arabia_pdf_url'] = $value('رابط ملف السيرة (PDF)');

            if (empty($normalized['data']['arabia_worker_number'])) {
                $normalized['errors'][] = 'رقم العامل في أربيا مطلوب.';
            }

            $rows[] = [
                'row_number' => $rowIndex,
                'raw_data' => $raw,
                'normalized_data' => $normalized['data'],
                'error_messages' => array_values(array_unique($normalized['errors'])),
                'status' => empty($normalized['errors']) ? 'pending' : 'invalid',
            ];
        }

        return $rows;
    }

    private function materializeArabiaMedia(array $data): array
    {
        $workerNumber = $data['arabia_worker_number'] ?? null;
        if (!$workerNumber) {
            return $data;
        }

        if (!empty($data['arabia_image_url'])) {
            $imagePath = $this->downloadArabiaAsset($data['arabia_image_url'], $workerNumber, 'image');
            if ($imagePath) {
                $data['image'] = $imagePath;
            }
        }
        if (!empty($data['arabia_pdf_url'])) {
            $cvPath = $this->downloadArabiaAsset($data['arabia_pdf_url'], $workerNumber, 'cv');
            if ($cvPath) {
                $data['cv_file'] = $cvPath;
            }
        }

        unset($data['arabia_image_url'], $data['arabia_pdf_url']);
        return $data;
    }

    private function downloadArabiaAsset(string $url, int $workerNumber, string $kind): ?string
    {
        try {
            $response = Http::retry(2, 250)->timeout(25)->get($url);
        } catch (\Throwable $exception) {
            logger()->warning('Arabia import media download failed.', [
                'worker_number' => $workerNumber,
                'kind' => $kind,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }

        if (!$response->successful() || $response->body() === '') {
            logger()->warning('Arabia import media URL is unavailable.', [
                'worker_number' => $workerNumber,
                'kind' => $kind,
                'url' => $url,
                'status' => $response->status(),
            ]);
            return null;
        }

        $pathExtension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]{2,5}$/', $pathExtension) ? $pathExtension : ($kind === 'cv' ? 'pdf' : 'jpg');
        $relativePath = 'images/Maid/arabia/' . $workerNumber . '_' . $kind . '_' . Str::random(8) . '.' . $extension;
        Storage::disk('public')->put($relativePath, $response->body());

        return $relativePath;
    }

    private function loadSpreadsheetForImport(string $path): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($path);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        return $reader->load($path);
    }

    private function persistImportRows(MaidBulkImport $import, array $rows): void
    {
        $now = now();

        foreach (array_chunk($rows, self::INSERT_CHUNK_SIZE) as $chunk) {
            MaidBulkImportRow::insert(array_map(function (array $row) use ($import, $now) {
                return [
                    'maid_bulk_import_id' => $import->id,
                    'row_number' => $row['row_number'],
                    'status' => $row['status'],
                    'raw_data' => json_encode($row['raw_data'], JSON_UNESCAPED_UNICODE),
                    'normalized_data' => json_encode($row['normalized_data'], JSON_UNESCAPED_UNICODE),
                    'error_messages' => json_encode($row['error_messages'], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $chunk));
        }

        $this->refreshImportCounters($import->fresh());
    }

    public function refreshImportCounters(MaidBulkImport $import): void
    {
        $statusCounts = $import->rows()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $importedRows = (int) ($statusCounts['imported'] ?? 0);
        $failedRows = (int) ($statusCounts['failed'] ?? 0);
        $invalidRows = (int) ($statusCounts['invalid'] ?? 0);
        $pendingRows = (int) ($statusCounts['pending'] ?? 0);
        $totalRows = (int) $statusCounts->sum();

        $status = 'pending_review';
        if ($pendingRows > 0) {
            $status = 'pending_review';
        } elseif ($invalidRows === 0 && $failedRows === 0) {
            $status = 'completed';
        } else {
            $status = 'completed_with_errors';
        }

        $import->update([
            'status' => $status,
            'total_rows' => $totalRows,
            'valid_rows' => $pendingRows + $importedRows,
            'invalid_rows' => $invalidRows,
            'imported_rows' => $importedRows,
            'failed_rows' => $failedRows,
            'processed_at' => now(),
        ]);
    }

    private function extractHeaders(Worksheet $sheet): array
    {
        $headers = [];

        foreach ($this->templateColumns() as $columnIndex => $column) {
            $value = (string) $sheet->getCell(Coordinate::stringFromColumnIndex($columnIndex + 1) . '1')->getValue();
            $headers[] = trim(str_replace('*', '', $value)) === $column['label'] ? $column['key'] : null;
        }

        return $headers;
    }

    private function headersMatchTemplate(array $headers): bool
    {
        $expectedKeys = collect($this->templateColumns())->pluck('key')->all();

        if ($headers === $expectedKeys) {
            return true;
        }

        $legacyHeaders = array_values(array_filter($headers, fn ($header) => $header !== null));

        $optionalNewKeys = ['id', 'added_by', 'created_at', 'gender'];

        $acceptedKeySets = [
            $expectedKeys,
            array_values(array_diff($expectedKeys, ['gender'])),
            array_values(array_diff($expectedKeys, $optionalNewKeys)),
            array_values(array_diff($expectedKeys, ['available', 'status'])),
            array_values(array_diff($expectedKeys, ['available', 'status', 'gender'])),
            array_values(array_diff($expectedKeys, array_merge(['available', 'status'], $optionalNewKeys))),
        ];

        foreach ($acceptedKeySets as $acceptedKeySet) {
            if ($legacyHeaders === $acceptedKeySet) {
                return true;
            }
        }

        return false;
    }

    private function cellValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $value = floor((float) $value) == (float) $value
                ? number_format((float) $value, 0, '.', '')
                : rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        $value = trim($this->normalizeDigits((string) $value));

        return $value === '' ? null : $value;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function isTemplateExampleRow(array $row): bool
    {
        return trim((string) ($row['passport_num'] ?? '')) === self::TEMPLATE_EXAMPLE_PASSPORT
            && trim((string) ($row['name:en'] ?? '')) === self::TEMPLATE_EXAMPLE_NAME;
    }

    private function normalizeRow(array $rawRow, array $lookups): array
    {
        $errors = [];
        $data = [];

        $data['name:en'] = trim((string) ($rawRow['name:en'] ?? ''));
        $data['passport_num'] = $this->normalizePassportNumber($rawRow['passport_num'] ?? '');
        $data['salary'] = $this->normalizeDecimal($rawRow['salary'] ?? null);
        $data['price'] = $this->normalizeDecimal($rawRow['price'] ?? null);
        $data['birth_date'] = $this->normalizeDate($rawRow['birth_date'] ?? null);
        $data['age'] = $this->normalizeDecimal($rawRow['age'] ?? null);
        $data['height'] = $this->normalizeDecimal($rawRow['height'] ?? null, false);
        $data['weight'] = $this->normalizeDecimal($rawRow['weight'] ?? null, false);
        $data['id_number'] = $this->trimmed($rawRow['id_number'] ?? null);
        $data['id_expiry_date'] = $this->normalizeDate($rawRow['id_expiry_date'] ?? null);
        $data['childrens_count'] = $this->normalizeInteger($rawRow['childrens_count'] ?? null);
        $data['duration'] = $this->trimmed($rawRow['duration'] ?? null) ?: self::DEFAULT_DURATION;
        $data['arrival_date'] = $this->normalizeDate($rawRow['arrival_date'] ?? null);
        $data['additional_notes'] = $this->trimmed($rawRow['additional_notes'] ?? null);
        $data['video_link'] = $this->trimmed($rawRow['video_link'] ?? null);
        $data['id'] = $this->normalizeInteger($rawRow['id'] ?? null);
        $data['added_by'] = $this->resolveAddedByValue($rawRow['added_by'] ?? null);
        $data['created_at'] = $this->normalizeDateTime($rawRow['created_at'] ?? null);
        $data['gender'] = $this->normalizeGender($rawRow['gender'] ?? null);

        [$data['status'], $statusError] = $this->resolveMappedValue(
            $rawRow['status'] ?? null,
            self::MAID_STATUS_ALIASES,
            'available'
        );
        if ($statusError) {
            $errors[] = 'الحالة: ' . $statusError;
        }

        [$availableValue, $availableError] = $this->normalizeOptionalBooleanField($rawRow['available'] ?? null);
        if ($availableError) {
            $errors[] = 'available: ' . $availableError;
        }
        $data['available'] = $availableValue ?? (in_array($data['status'], ['available', 'completed'], true) ? '1' : '0');

        if (!$data['age'] && $data['birth_date']) {
            $data['age'] = (string) Carbon::parse($data['birth_date'])->age;
        }

        [$data['nationality_id'], $nationalityError, $nationality] = $this->resolveEntity($rawRow['nationality'] ?? null, $lookups['nationalities']);
        if ($nationalityError) {
            $errors[] = 'الجنسية: ' . $nationalityError;
        }

        [$data['office_id'], $officeError, $office] = $this->resolveOfficeEntity($rawRow['office'] ?? null, $lookups['offices'], $nationality);
        if ($officeError) {
            $errors[] = 'المكتب الخارجي: ' . $officeError;
        }

        [$data['religion_id'], $religionError] = $this->resolveEntity($rawRow['religion'] ?? null, $lookups['religions']);
        if ($religionError) {
            $errors[] = 'الديانة: ' . $religionError;
        }

        [$data['type_id'], $typeError, $jobType] = $this->resolveEntity($rawRow['type_id'] ?? null, $lookups['job_types']);
        if ($typeError) {
            $errors[] = 'نوع السيرة: ' . $typeError;
        }

        [$data['job_id'], $jobErrors] = $this->resolveManyEntities($rawRow['job_titles'] ?? null, $lookups['job_titles']);
        foreach ($jobErrors as $jobError) {
            $errors[] = 'المهنة: ' . $jobError;
        }

        [$data['skills'], $skillErrors] = $this->resolveManyEntities($rawRow['skills'] ?? null, $lookups['skills'], false);
        foreach ($skillErrors as $skillError) {
            $errors[] = 'المهارات: ' . $skillError;
        }

        [$data['languages'], $data['percentage'], $languageErrors] = $this->resolveLanguages(
            $rawRow['languages'] ?? null,
            $rawRow['language_percentages'] ?? null
        );
        foreach ($languageErrors as $languageError) {
            $errors[] = $languageError;
        }

        [$data['request_type'], $requestTypeError] = $this->resolveMappedValue(
            $rawRow['request_type'] ?? null,
            self::REQUEST_TYPE_ALIASES,
            Maid::REQUEST_TYPE_CUSTOM
        );
        if ($requestTypeError) {
            $errors[] = 'نوع الطلب: ' . $requestTypeError;
        }

        [$data['experience_status'], $experienceStatusError] = $this->resolveMappedValue(
            $rawRow['experience_status'] ?? null,
            self::EXPERIENCE_STATUS_ALIASES,
            'no_experience'
        );
        if ($experienceStatusError) {
            $errors[] = 'حالة الخبرة: ' . $experienceStatusError;
        }

        $data['experience_years'] = $this->normalizeDecimal($rawRow['experience_years'] ?? null, false);
        if (($data['experience_status'] ?? null) === 'no_experience') {
            $data['experience_years'] = '0';
        }

        $maritalStatusAr = $this->trimmed($rawRow['marital_status:ar'] ?? null);
        if ($maritalStatusAr) {
            $maritalStatusEn = $this->resolveMaritalStatus($maritalStatusAr);

            if (!$maritalStatusEn) {
                $errors[] = 'الحالة الاجتماعية غير معروفة.';
            } else {
                $data['marital_status:ar'] = $maritalStatusAr;
                $data['marital_status:en'] = $maritalStatusEn;
            }
        }

        [$createShelter, $createShelterError] = $this->normalizeBooleanField($rawRow['create_shelter_contract'] ?? null, '0');
        if ($createShelterError) {
            $errors[] = 'إنشاء عقد الإيواء: ' . $createShelterError;
        }
        $data['create_shelter_contract'] = $createShelter;

        [$isShow, $isShowError] = $this->normalizeBooleanField($rawRow['is_show'] ?? null, '1');
        if ($isShowError) {
            $errors[] = 'إظهار في الموقع: ' . $isShowError;
        }
        $data['is_show'] = $isShow;

        if (empty($data['religion_id'])) {
            $data['religion_id'] = $lookups['default_religion_id'];
        }

        if ($nationality) {
            if (($data['salary'] === null || $data['salary'] === '') && $nationality->monthly_salary !== null) {
                $data['salary'] = (string) $nationality->monthly_salary;
            }

            if (($data['price'] === null || $data['price'] === '') && $nationality->price !== null) {
                $data['price'] = (string) $nationality->price;
            }
        }

        if ($jobType && $this->isRentalJobType($jobType)) {
            $data['rental_finance_items'] = $this->rentalMaidFinanceService->getFormItems();
            $data['rental_finance_items_confirmed'] = 1;
        }

        $validator = Validator::make($data, [
            'name:en' => 'required|string|max:255',
            'passport_num' => 'required|string|max:100',
            'nationality_id' => 'required|exists:nationalities,id',
            'religion_id' => 'nullable|exists:religions,id',
            'type_id' => 'required|exists:job_types,id',
            'job_id' => 'required|array|min:1',
            'job_id.*' => 'required|exists:job_titles,id',
            'office_id' => 'nullable|exists:recruitment_offices,id',
            'salary' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'birth_date' => 'nullable|date',
            'age' => 'nullable|numeric|min:16|max:80',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'childrens_count' => 'nullable|integer|min:0',
            'arrival_date' => 'nullable|date',
            'id_expiry_date' => 'nullable|date',
            'video_link' => 'nullable|url',
            'experience_status' => 'required|in:has_experience,no_experience',
            'experience_years' => 'nullable|numeric|min:0',
            'request_type' => 'nullable|in:known,specific,custom_spec',
            'is_show' => 'nullable|in:0,1',
            'create_shelter_contract' => 'nullable|in:0,1',
            'available' => 'nullable|in:0,1',
            'status' => 'nullable|in:' . implode(',', array_keys(self::MAID_STATUS_ALIASES)),
            'id' => 'nullable|integer|min:1',
            'added_by' => 'nullable|integer|min:1',
            'created_at' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
        ], [
            'video_link.url' => 'رابط الفيديو غير صالح.',
        ]);

        if (($data['experience_status'] ?? null) === 'has_experience' && ($data['experience_years'] === null || $data['experience_years'] === '')) {
            $validator->errors()->add('experience_years', 'سنوات الخبرة مطلوبة عندما تكون حالة الخبرة "سبق لها العمل".');
        }

        if ($validator->fails()) {
            $errors = array_merge($errors, $validator->errors()->all());
        }

        return [
            'data' => $this->cleanData($data),
            'errors' => array_values(array_unique(array_filter($errors))),
        ];
    }

    private function cleanData(array $data): array
    {
        return collect($data)
            ->reject(function ($value) {
                return $value === null || $value === '';
            })
            ->all();
    }

    private function lookupEntities(): array
    {
        $nationalities = Nationality::query()->with('translations')->get();
        $offices = RecruitmentOffices::query()->with('translations')->get();
        $religions = Religion::query()->with('translations')->get();
        $jobTypes = JobType::query()->with('translations')->get();
        $jobTitles = JobTitle::query()->with('translations')->get();
        $skills = Skill::query()->with('translations')->get();

        return [
            'nationalities' => $this->buildEntityLookup($nationalities, ['sub_title', 'title']),
            'offices' => $this->buildEntityLookup($offices, ['title']),
            'religions' => $this->buildEntityLookup($religions, ['title']),
            'job_types' => $this->buildEntityLookup($jobTypes, ['title']),
            'job_titles' => $this->buildEntityLookup($jobTitles, ['title']),
            'skills' => $this->buildEntityLookup($skills, ['title']),
            'default_religion_id' => Religion::query()->orderBy('id')->value('id'),
        ];
    }

    private function buildEntityLookup(Collection $records, array $fields): array
    {
        $byId = [];
        $byValue = [];

        foreach ($records as $record) {
            $byId[(string) $record->id] = $record;

            foreach ($this->entityValues($record, $fields) as $value) {
                $normalized = $this->normalizeText($value);
                if ($normalized === '') {
                    continue;
                }

                $byValue[$normalized] = $byValue[$normalized] ?? [];
                $byValue[$normalized][$record->id] = $record;
            }
        }

        return [
            'by_id' => $byId,
            'by_value' => $byValue,
        ];
    }

    private function entityValues($record, array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[] = $record->{$field} ?? null;
        }

        if ($record->relationLoaded('translations')) {
            foreach ($record->translations as $translation) {
                foreach ($fields as $field) {
                    $values[] = $translation->{$field} ?? null;
                }
            }
        }

        return array_values(array_unique(array_filter($values)));
    }

    private function resolveEntity($value, array $lookup): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [null, null, null];
        }

        if (isset($lookup['by_id'][(string) $value])) {
            return [(int) $value, null, $lookup['by_id'][(string) $value]];
        }

        $normalized = $this->normalizeText($value);
        $matches = $lookup['by_value'][$normalized] ?? [];

        if (count($matches) === 1) {
            $record = Arr::first($matches);

            return [$record->id, null, $record];
        }

        if (count($matches) > 1) {
            $record = Arr::first($matches);

            return [$record->id, null, $record];
        }

        return [null, 'القيمة غير موجودة في النظام.', null];
    }

    private function resolveOfficeEntity($value, array $lookup, ?Nationality $nationality): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [null, null, null];
        }

        if (isset($lookup['by_id'][(string) $value])) {
            return [(int) $value, null, $lookup['by_id'][(string) $value]];
        }

        $matches = $lookup['by_value'][$this->normalizeText($value)] ?? [];

        if ($nationality && count($matches) > 1) {
            $sameNationality = collect($matches)
                ->first(fn ($record) => (int) ($record->nationality_id ?? 0) === (int) $nationality->id);

            if ($sameNationality) {
                return [$sameNationality->id, null, $sameNationality];
            }
        }

        return $this->resolveEntity($value, $lookup);
    }

    private function resolveManyEntities($value, array $lookup, bool $required = true): array
    {
        $items = $this->splitMultiValue($value);
        $ids = [];
        $errors = [];

        if ($items === [] && !$required) {
            return [[], []];
        }

        foreach ($items as $item) {
            [$id, $error] = $this->resolveEntity($item, $lookup);
            if ($error) {
                $errors[] = "\"{$item}\" {$error}";
                continue;
            }

            if ($id !== null) {
                $ids[] = (int) $id;
            }
        }

        $ids = array_values(array_unique($ids));

        if ($required && $ids === []) {
            $errors[] = 'يجب إدخال قيمة واحدة على الأقل.';
        }

        return [$ids, $errors];
    }

    private function resolveLanguages($languagesValue, $percentagesValue): array
    {
        $languages = [];
        $errors = [];

        foreach ($this->splitMultiValue($languagesValue) as $languageToken) {
            $resolved = $this->resolveAlias($languageToken, self::LANGUAGE_ALIASES);

            if ($resolved === null) {
                $errors[] = "اللغة \"{$languageToken}\" غير معتمدة.";
                continue;
            }

            $languages[$resolved] = $resolved;
        }

        $percentages = [];
        $pairs = $this->splitMultiValue($percentagesValue);

        foreach ($pairs as $pair) {
            $parts = preg_split('/\s*:\s*/u', $pair);
            if (count($parts) !== 2) {
                $errors[] = "تنسيق نسبة اللغة \"{$pair}\" غير صحيح.";
                continue;
            }

            $language = $this->resolveAlias($parts[0], self::LANGUAGE_ALIASES);
            if ($language === null) {
                $errors[] = "اللغة في النسبة \"{$parts[0]}\" غير معتمدة.";
                continue;
            }

            if (!is_numeric($parts[1]) || (float) $parts[1] < 0 || (float) $parts[1] > 100) {
                $errors[] = "نسبة اللغة \"{$pair}\" يجب أن تكون من 0 إلى 100.";
                continue;
            }

            $percentages[$language] = (string) (float) $parts[1];
            $languages[$language] = $language;
        }

        return [$languages, $percentages, $errors];
    }

    private function resolveMappedValue($value, array $aliases, ?string $default = null): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [$default, null];
        }

        $resolved = $this->resolveAlias($value, $aliases);
        if ($resolved === null) {
            return [$default, 'القيمة غير معتمدة.'];
        }

        return [$resolved, null];
    }

    private function resolveAlias(string $value, array $aliases): ?string
    {
        $normalizedInput = $this->normalizeText($value);

        foreach ($aliases as $normalizedValue => $candidates) {
            foreach ($candidates as $candidate) {
                if ($this->normalizeText($candidate) === $normalizedInput) {
                    return $normalizedValue;
                }
            }
        }

        return null;
    }

    private function splitMultiValue($value): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [];
        }

        return collect(preg_split('/[\|\n\r,،;]+/u', $value))
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeText(?string $value): string
    {
        $value = $this->normalizeDigits((string) $value);
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace(['أ', 'إ', 'آ', 'ؤ', 'ئ'], ['ا', 'ا', 'ا', 'و', 'ي'], $value);
        $value = str_replace(['ة', 'ى', 'ـ'], ['ه', 'ي', ''], $value);
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value);

        return $value ?? '';
    }

    private function normalizeDate($value): ?string
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return null;
        }

        try {
            $value = $this->normalizeDigits($value);

            if (preg_match('/^(19|20)\d{6}$/', trim($value))) {
                return Carbon::createFromFormat('Ymd', trim($value))->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y', 'm-d-Y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, trim($value))->format('Y-m-d');
                } catch (\Throwable) {
                    // Try the next common import format.
                }
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    private function normalizeDateTime($value): ?string
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return null;
        }

        try {
            $value = $this->normalizeDigits($value);

            if (preg_match('/^(19|20)\d{6}$/', trim($value))) {
                return Carbon::createFromFormat('Ymd', trim($value))->startOfDay()->format('Y-m-d H:i:s');
            }

            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            }

            foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y/m/d H:i:s', 'Y/m/d H:i', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd-m-Y H:i:s', 'd-m-Y H:i'] as $format) {
                try {
                    return Carbon::createFromFormat($format, trim($value))->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    // Try the next common import format.
                }
            }

            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $exception) {
            return $value;
        }
    }

    private function normalizeDecimal($value, bool $formatWithTwoDecimals = true): ?string
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return null;
        }

        $value = str_replace(',', '', $this->normalizeDigits($value));

        if (!is_numeric($value)) {
            return $value;
        }

        return $formatWithTwoDecimals
            ? number_format((float) $value, 2, '.', '')
            : (string) (float) $value;
    }

    private function normalizeInteger($value)
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return null;
        }

        $value = $this->normalizeDigits($value);

        if (preg_match('/^(.+)\.0+$/', $value, $matches)) {
            $value = $matches[1];
        }

        if (!is_numeric($value)) {
            return $value;
        }

        return (string) (int) $value;
    }

    private function normalizeGender($value): ?string
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalizeText($value);

        return match ($normalized) {
            'male', 'ذكر', 'رجل', 'سائق' => 'male',
            'female', 'انثي', 'انثى', 'امراه', 'امراة', 'عامله' => 'female',
            default => $value,
        };
    }

    private function normalizePassportNumber($value): string
    {
        $value = $this->normalizeDigits((string) $value);
        $value = preg_replace('/\s+/u', '', trim($value));

        if (preg_match('/^(.+)\.0+$/', $value, $matches)) {
            $value = $matches[1];
        }

        return mb_strtoupper($value);
    }

    private function resolveMaritalStatus(string $value): ?string
    {
        $normalized = $this->normalizeText($value);

        foreach (self::MARITAL_STATUS_MAP as $label => $status) {
            if ($this->normalizeText($label) === $normalized) {
                return $status;
            }
        }

        return match ($normalized) {
            'single', 'اعزب', 'عازب', 'عزباء' => 'single',
            'married', 'متزوج', 'متزوجه' => 'married',
            default => null,
        };
    }

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    private function prepareLargeImportRuntime(): void
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        @ini_set('max_execution_time', '0');
    }

    private function resolveAddedByValue($value): ?string
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            $adminId = DB::table('admins')->where('id', (int) $value)->value('id');

            return $adminId ? (string) $adminId : null;
        }

        try {
            $adminId = DB::table('admins')
                ->where(function ($query) use ($value) {
                    $query->where('name', $value)
                        ->orWhere('email', $value);

                    if (Schema::hasColumn('admins', 'username')) {
                        $query->orWhere('username', $value);
                    }
                })
                ->value('id');

            return $adminId ? (string) $adminId : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function normalizeBooleanField($value, string $default): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [$default, null];
        }

        $truthy = ['1', 'true', 'yes', 'نعم'];
        $falsy = ['0', 'false', 'no', 'لا'];
        $normalized = mb_strtolower($value);

        if (in_array($normalized, $truthy, true)) {
            return ['1', null];
        }

        if (in_array($normalized, $falsy, true)) {
            return ['0', null];
        }

        return [$default, 'القيمة يجب أن تكون نعم أو لا.'];
    }

    private function normalizeOptionalBooleanField($value): array
    {
        $value = $this->trimmed($value);
        if ($value === null) {
            return [null, null];
        }

        return $this->normalizeBooleanField($value, '1');
    }

    private function trimmed($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isRentalJobType(JobType $jobType): bool
    {
        $title = $this->normalizeText($jobType->title ?? '');

        return (int) $jobType->id === 2
            || str_contains($title, 'ايجار')
            || str_contains($title, 'تاجير')
            || str_contains($title, 'rent');
    }

    private function extractErrors(\Throwable $exception): array
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())->flatten()->values()->all();
        }

        return [$exception->getMessage()];
    }
}
