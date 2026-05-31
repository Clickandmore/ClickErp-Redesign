<?php

namespace App\Services\ingaz;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DataImportTemplateService
{
    public function templates(): array
    {
        return [
            'clients' => [
                'label' => 'العملاء',
                'route_hint' => '/admin/clients',
                'icon' => 'fas fa-users',
                'description' => 'بيانات العملاء الأساسية. رقم الهوية مطلوب وغير مكرر ويستخدم للربط مع الطلبات والعقود.',
                'filename' => 'clients-import-template.xlsx',
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'required' => false, 'example' => '172'],
                    ['key' => 'name', 'label' => 'اسم العميل', 'required' => true, 'example' => 'محمد أحمد علي'],
                    ['key' => 'phone', 'label' => 'رقم الجوال', 'required' => true, 'example' => '0500000000'],
                    ['key' => 'id_number', 'label' => 'رقم الهوية/الإقامة', 'required' => true, 'example' => '1000000000'],
                    ['key' => 'email', 'label' => 'البريد الإلكتروني', 'required' => false, 'example' => 'client@example.com'],
                    ['key' => 'nationality', 'label' => 'الجنسية', 'required' => false, 'example' => 'سعودي'],
                    ['key' => 'birthdate', 'label' => 'تاريخ الميلاد', 'required' => false, 'example' => '1990-01-01'],
                    ['key' => 'marital_status', 'label' => 'الحالة الاجتماعية', 'required' => false, 'example' => 'متزوج', 'valid_values' => ['أعزب', 'متزوج', 'مطلق', 'أرمل', 'single', 'married', 'divorced', 'widowed']],
                    ['key' => 'phone2', 'label' => 'رقم جوال إضافي', 'required' => false, 'example' => '0550000000'],
                    ['key' => 'client_category', 'label' => 'تصنيف العميل', 'required' => false, 'example' => 'أفراد'],
                    ['key' => 'account_id', 'label' => 'Account ID', 'required' => false, 'example' => '1001'],
                    ['key' => 'city', 'label' => 'المدينة', 'required' => false, 'example' => 'الرياض'],
                    ['key' => 'address', 'label' => 'العنوان', 'required' => false, 'example' => 'حي الياسمين'],
                    ['key' => 'rooms_num', 'label' => 'عدد الغرف', 'required' => false, 'example' => '4'],
                    ['key' => 'family_num', 'label' => 'عدد أفراد الأسرة', 'required' => false, 'example' => '5'],
                    ['key' => 'children', 'label' => 'عدد الأطفال', 'required' => false, 'example' => '2'],
                    ['key' => 'job', 'label' => 'الوظيفة', 'required' => false, 'example' => 'موظف'],
                    ['key' => 'status', 'label' => 'الحالة', 'required' => false, 'example' => 'نشط'],
                    ['key' => 'created_by', 'label' => 'أضيف بواسطة', 'required' => false, 'example' => '1'],
                    ['key' => 'notes', 'label' => 'ملاحظات', 'required' => false, 'example' => 'عميل مستورد من ملف خارجي'],
                ],
            ],
            'maids' => [
                'label' => 'السير الذاتية',
                'route_hint' => '/admin/maids',
                'icon' => 'fas fa-id-card',
                'description' => 'استيراد السير الذاتية يستخدم مسار المراجعة والاعتماد الموجود.',
                'external_template_route' => 'maids.bulk-import.template',
                'external_upload_route' => 'maids.bulk-import.store',
                'columns' => [],
            ],
            'rental_maids' => [
                'label' => 'سير الإيجار',
                'route_hint' => '/admin/maids/rental',
                'icon' => 'fas fa-user-clock',
                'description' => 'بيانات العاملات المتاحة للتأجير بنفس حقول شاشة إنشاء السيرة.',
                'filename' => 'rental-maids-import-template.xlsx',
                'columns' => [
                    ['key' => 'maid_code', 'label' => 'رقم السيرة', 'required' => false, 'example' => 'RNT-001'],
                    ['key' => 'name_en', 'label' => 'الاسم بالإنجليزية', 'required' => true, 'example' => 'EDIT_ME_NAME'],
                    ['key' => 'passport_num', 'label' => 'رقم الجواز', 'required' => true, 'example' => 'P0000001'],
                    ['key' => 'nationality', 'label' => 'الجنسية', 'required' => true, 'example' => 'أوغندا'],
                    ['key' => 'office', 'label' => 'مكتب الاستقدام', 'required' => true, 'example' => 'مكتب خارجي'],
                    ['key' => 'type_id', 'label' => 'نوع السيرة', 'required' => false, 'example' => 'إيجار'],
                    ['key' => 'job_titles', 'label' => 'المهنة', 'required' => true, 'example' => 'عاملة منزلية'],
                    ['key' => 'religion', 'label' => 'الديانة', 'required' => false, 'example' => 'مسلمة'],
                    ['key' => 'request_type', 'label' => 'نوع الطلب', 'required' => false, 'example' => 'حسب المواصفات'],
                    ['key' => 'monthly_salary', 'label' => 'الراتب الشهري', 'required' => false, 'example' => '1500'],
                    ['key' => 'rental_price', 'label' => 'تكلفة/سعر السيرة', 'required' => false, 'example' => '2500'],
                    ['key' => 'birth_date', 'label' => 'تاريخ الميلاد', 'required' => false, 'example' => '1998-01-01'],
                    ['key' => 'age', 'label' => 'العمر', 'required' => false, 'example' => '27'],
                    ['key' => 'height', 'label' => 'الطول', 'required' => false, 'example' => '160'],
                    ['key' => 'weight', 'label' => 'الوزن', 'required' => false, 'example' => '60'],
                    ['key' => 'id_number', 'label' => 'رقم الهوية', 'required' => false, 'example' => 'EDIT-ID-001'],
                    ['key' => 'id_expiry_date', 'label' => 'تاريخ انتهاء الهوية', 'required' => false, 'example' => now()->addYear()->format('Y-m-d')],
                    ['key' => 'marital_status', 'label' => 'الحالة الاجتماعية', 'required' => false, 'example' => 'عازبة'],
                    ['key' => 'childrens_count', 'label' => 'عدد الأطفال', 'required' => false, 'example' => '0'],
                    ['key' => 'duration', 'label' => 'مدة العقد', 'required' => false, 'example' => '24 شهر'],
                    ['key' => 'experience_status', 'label' => 'حالة الخبرة', 'required' => true, 'example' => 'سبق لها العمل'],
                    ['key' => 'experience_years', 'label' => 'سنوات الخبرة', 'required' => false, 'example' => '2'],
                    ['key' => 'skills', 'label' => 'المهارات', 'required' => false, 'example' => 'طبخ|تنظيف'],
                    ['key' => 'languages', 'label' => 'اللغات', 'required' => false, 'example' => 'arabic|english'],
                    ['key' => 'language_percentages', 'label' => 'نسب اللغات', 'required' => false, 'example' => 'arabic:90|english:70'],
                    ['key' => 'availability_status', 'label' => 'حالة التوفر', 'required' => false, 'example' => 'متاحة'],
                    ['key' => 'available_at', 'label' => 'متاحة من', 'required' => false, 'example' => now()->format('Y-m-d')],
                    ['key' => 'arrival_date', 'label' => 'تاريخ الوصول', 'required' => false, 'example' => now()->format('Y-m-d')],
                    ['key' => 'create_shelter_contract', 'label' => 'إنشاء عقد إيواء', 'required' => false, 'example' => 'لا'],
                    ['key' => 'is_show', 'label' => 'إظهار في الموقع', 'required' => false, 'example' => 'نعم'],
                    ['key' => 'is_pinned', 'label' => 'تثبيت السيرة', 'required' => false, 'example' => 'لا'],
                    ['key' => 'video_link', 'label' => 'رابط الفيديو', 'required' => false, 'example' => 'https://example.com/video'],
                    ['key' => 'finance_income', 'label' => 'إيرادات التأجير', 'required' => false, 'example' => '0'],
                    ['key' => 'finance_expense', 'label' => 'مصروفات التأجير', 'required' => false, 'example' => '0'],
                    ['key' => 'notes', 'label' => 'ملاحظات إضافية', 'required' => false, 'example' => 'سيرة مخصصة للتأجير'],
                ],
            ],
            'recruitment_contracts' => [
                'label' => 'عقود الاستقدام',
                'route_hint' => '/admin/RecruitmentContract',
                'icon' => 'fas fa-file-signature',
                'description' => 'عقود الاستقدام مع بيانات العميل والعاملة ومرحلة العقد الحالية.',
                'filename' => 'recruitment-contracts-import-template.xlsx',
                'columns' => [
                    ['key' => 'contract_number', 'label' => 'رقم العقد', 'required' => false, 'example' => 'RC-0001'],
                    ['key' => 'client_phone', 'label' => 'جوال العميل', 'required' => true, 'example' => '0500000000'],
                    ['key' => 'client_id_number', 'label' => 'رقم هوية العميل', 'required' => true, 'example' => '1000000000'],
                    ['key' => 'client_name', 'label' => 'اسم العميل', 'required' => false, 'example' => 'محمد أحمد'],
                    ['key' => 'maid_passport', 'label' => 'رقم جواز العاملة', 'required' => true, 'example' => 'P0000001'],
                    ['key' => 'nationality', 'label' => 'الجنسية', 'required' => false, 'example' => 'الفلبين'],
                    ['key' => 'contract_date', 'label' => 'تاريخ العقد', 'required' => true, 'example' => now()->format('Y-m-d')],
                    ['key' => 'status', 'label' => 'حالة العقد', 'required' => true, 'example' => 'عقود مساند', 'valid_values' => $this->recruitmentContractStatusLabels()],
                    ['key' => 'amount', 'label' => 'قيمة العقد', 'required' => false, 'example' => '12000'],
                    ['key' => 'paid_amount', 'label' => 'المدفوع', 'required' => false, 'example' => '5000'],
                    ['key' => 'notes', 'label' => 'ملاحظات', 'required' => false, 'example' => 'عقد مستورد'],
                ],
            ],
            'shelter_contracts' => [
                'label' => 'الإيواء',
                'route_hint' => '/admin/ShelterContract',
                'icon' => 'fas fa-house-user',
                'description' => 'بيانات عقود الإيواء ومكان الإيواء وحالة العاملة.',
                'filename' => 'shelter-contracts-import-template.xlsx',
                'columns' => [
                    ['key' => 'contract_number', 'label' => 'رقم العقد/المرجع', 'required' => false, 'example' => 'SH-0001'],
                    ['key' => 'client_phone', 'label' => 'جوال العميل', 'required' => false, 'example' => '0500000000'],
                    ['key' => 'client_id_number', 'label' => 'رقم هوية العميل', 'required' => false, 'example' => '1000000000'],
                    ['key' => 'client_name', 'label' => 'اسم العميل', 'required' => false, 'example' => 'محمد أحمد'],
                    ['key' => 'maid_passport', 'label' => 'رقم الجواز', 'required' => true, 'example' => 'P0000001'],
                    ['key' => 'maid_name', 'label' => 'اسم العاملة', 'required' => false, 'example' => 'EDIT_ME_NAME'],
                    ['key' => 'nationality', 'label' => 'الجنسية', 'required' => false, 'example' => 'كينيا'],
                    ['key' => 'shelter_place', 'label' => 'مكان الإيواء', 'required' => true, 'example' => 'السكن الرئيسي'],
                    ['key' => 'receipt_date', 'label' => 'تاريخ الاستلام', 'required' => true, 'example' => now()->format('Y-m-d')],
                    ['key' => 'source', 'label' => 'مصدر الاستلام', 'required' => false, 'example' => 'عميل'],
                    ['key' => 'status', 'label' => 'الحالة', 'required' => false, 'example' => 'داخل الإيواء'],
                    ['key' => 'notes', 'label' => 'ملاحظات', 'required' => false, 'example' => 'إيواء مستورد'],
                ],
            ],
            'rent_contracts' => [
                'label' => 'عقود التأجير',
                'route_hint' => '/admin/RentContract',
                'icon' => 'fas fa-file-invoice-dollar',
                'description' => 'عقود التأجير الحالية والمكتملة مع المدة والقيمة.',
                'filename' => 'rent-contracts-import-template.xlsx',
                'columns' => [
                    ['key' => 'contract_number', 'label' => 'رقم عقد التأجير', 'required' => false, 'example' => 'RN-0001'],
                    ['key' => 'client_phone', 'label' => 'جوال العميل', 'required' => true, 'example' => '0500000000'],
                    ['key' => 'client_id_number', 'label' => 'رقم هوية العميل', 'required' => true, 'example' => '1000000000'],
                    ['key' => 'maid_passport', 'label' => 'رقم جواز العاملة', 'required' => true, 'example' => 'P0000001'],
                    ['key' => 'start_date', 'label' => 'تاريخ البداية', 'required' => true, 'example' => now()->format('Y-m-d')],
                    ['key' => 'end_date', 'label' => 'تاريخ النهاية', 'required' => false, 'example' => now()->addMonth()->format('Y-m-d')],
                    ['key' => 'duration_months', 'label' => 'المدة بالأشهر', 'required' => false, 'example' => '1'],
                    ['key' => 'monthly_amount', 'label' => 'المبلغ الشهري', 'required' => false, 'example' => '2500'],
                    ['key' => 'status', 'label' => 'حالة العقد', 'required' => true, 'example' => 'نشط', 'valid_values' => $this->rentalContractStatusLabels()],
                    ['key' => 'notes', 'label' => 'ملاحظات', 'required' => false, 'example' => 'عقد تأجير مستورد'],
                ],
            ],
            'sponsorship_transfers' => [
                'label' => 'طلبات النقل',
                'route_hint' => '/admin/SponsorshipTransfer',
                'icon' => 'fas fa-exchange-alt',
                'description' => 'طلبات نقل الكفالة ومراحلها وبيانات العميل والعاملة.',
                'filename' => 'sponsorship-transfers-import-template.xlsx',
                'columns' => [
                    ['key' => 'request_number', 'label' => 'رقم الطلب', 'required' => false, 'example' => 'ST-0001'],
                    ['key' => 'client_phone', 'label' => 'جوال العميل', 'required' => true, 'example' => '0500000000'],
                    ['key' => 'client_id_number', 'label' => 'رقم هوية العميل', 'required' => true, 'example' => '1000000000'],
                    ['key' => 'maid_passport', 'label' => 'رقم جواز العاملة', 'required' => true, 'example' => 'P0000001'],
                    ['key' => 'request_date', 'label' => 'تاريخ الطلب', 'required' => true, 'example' => now()->format('Y-m-d')],
                    ['key' => 'transfer_status', 'label' => 'حالة النقل', 'required' => true, 'example' => 'قيد المراجعة'],
                    ['key' => 'trial_start_date', 'label' => 'بداية التجربة', 'required' => false, 'example' => now()->format('Y-m-d')],
                    ['key' => 'amount', 'label' => 'القيمة', 'required' => false, 'example' => '8000'],
                    ['key' => 'notes', 'label' => 'ملاحظات', 'required' => false, 'example' => 'طلب نقل مستورد'],
                ],
            ],
        ];
    }

    public function template(string $type): ?array
    {
        return $this->templates()[$type] ?? null;
    }

    private function recruitmentContractStatusLabels(): array
    {
        return [
            'بانتظار اختيار العاملة',
            'بانتظار الاعتماد',
            'اعتماد العقود',
            'عقود مساند',
            'تفويض',
            'التفييز',
            'تذكرة',
            'مرحلة الوصول',
            'عقد مكتمل',
            'مرتجع',
            'عقد ساري',
            'عقد إيواء',
            'في فترة التجربة',
            'مرحلة الاجراءات',
            'تم التعاقد مع العميل',
            'تم اختيار المندوب',
            'تحت الضمان',
            'عقد مغلق',
            'ملغي',
        ];
    }

    private function rentalContractStatusLabels(): array
    {
        return [
            'جديد',
            'بانتظار التوقيع',
            'تم إرسال العقد',
            'تم توقيع العقد',
            'تم تسليم العاملة',
            'نشط',
            'مكتمل',
            'تم النقل',
            'ملغي',
            'معلق',
        ];
    }

    public function createTemplateDownloadResponse(string $type)
    {
        $template = $this->template($type);

        abort_if(!$template || !empty($template['external_template_route']), 404);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('البيانات');
        $sheet->setRightToLeft(true);

        foreach ($template['columns'] as $index => $column) {
            $columnIndex = $index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue("{$columnLetter}1", $column['label']);
            $sheet->setCellValue("{$columnLetter}2", $column['example'] ?? '');
            $sheet->setCellValue("{$columnLetter}3", $column['required'] ? 'مطلوب' : 'اختياري');
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($template['columns']));
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
        ]);
        $sheet->getStyle("A2:{$lastColumn}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');

        foreach (range(1, count($template['columns'])) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('تعليمات');
        $instructions->setRightToLeft(true);
        $instructions->setCellValue('A1', $template['label']);
        $instructions->setCellValue('A2', $template['description']);
        $instructions->setCellValue('A3', 'لا تغير أسماء الأعمدة. استخدم الصف الثاني كمثال، واحذف أو عدل البيانات قبل الرفع.');
        $instructions->setCellValue('A4', 'الصف الثالث يوضح هل الحقل مطلوب أو اختياري.');

        $instructionRow = 5;
        foreach ($template['columns'] as $column) {
            if (empty($column['valid_values'])) {
                continue;
            }

            $instructions->setCellValue(
                "A{$instructionRow}",
                ($column['label'] ?? $column['key']) . ': ' . implode('، ', $column['valid_values'])
            );
            $instructionRow++;
        }

        $instructions->getStyle('A1:A' . max(4, $instructionRow - 1))->getAlignment()->setWrapText(true);
        $instructions->getColumnDimension('A')->setWidth(90);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'data-import-template');
        $writer->save($tempFile);

        return response()
            ->download($tempFile, $template['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function storeUploadedFile(string $type, UploadedFile $file, ?int $adminId): string
    {
        $safeName = preg_replace('/[^A-Za-z0-9_.-]/', '_', $file->getClientOriginalName());
        $prefix = now()->format('Ymd_His') . '_' . ($adminId ?: 'guest');

        return $file->storeAs("data-imports/{$type}", "{$prefix}_{$safeName}");
    }

    public function recentUploads(int $limit = 20): array
    {
        $files = collect(Storage::allFiles('data-imports'))
            ->map(fn (string $path) => [
                'path' => $path,
                'type' => explode('/', $path)[1] ?? '-',
                'name' => basename($path),
                'size' => Storage::size($path),
                'date' => Storage::lastModified($path),
            ])
            ->sortByDesc('date')
            ->take($limit)
            ->values();

        return $files->all();
    }
}
