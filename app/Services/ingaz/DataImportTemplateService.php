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
        $templates = [
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
            'recruitment_offices' => [
                'label' => 'مكاتب الاستقدام',
                'route_hint' => '/admin/recruitment-offices',
                'icon' => 'fas fa-building',
                'description' => 'إدخال أو تحديث مكاتب الاستقدام وربطها بالجنسية وبيانات التواصل والبنك.',
                'filename' => 'recruitment-offices-import-template.xlsx',
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'required' => false, 'example' => '15'],
                    ['key' => 'title_ar', 'label' => 'اسم المكتب بالعربية', 'required' => true, 'example' => 'مكتب خارجي'],
                    ['key' => 'title_en', 'label' => 'اسم المكتب بالإنجليزية', 'required' => false, 'example' => 'External Office'],
                    ['key' => 'nationality', 'label' => 'الجنسية', 'required' => true, 'example' => 'الفلبين'],
                    ['key' => 'status', 'label' => 'الحالة', 'required' => false, 'example' => 'نشط', 'valid_values' => ['نشط', 'غير نشط', 'active', 'inactive']],
                    ['key' => 'is_show', 'label' => 'إظهار في الموقع', 'required' => false, 'example' => 'لا', 'valid_values' => ['نعم', 'لا']],
                    ['key' => 'phone_office', 'label' => 'هاتف المكتب', 'required' => false, 'example' => '+639000000000'],
                    ['key' => 'license_number', 'label' => 'رقم الترخيص', 'required' => false, 'example' => 'LIC-001'],
                    ['key' => 'street', 'label' => 'العنوان/الشارع', 'required' => false, 'example' => 'Manila'],
                    ['key' => 'manager_name', 'label' => 'اسم المدير', 'required' => false, 'example' => 'Ahmed Ali'],
                    ['key' => 'manager_phone', 'label' => 'جوال المدير', 'required' => false, 'example' => '+639111111111'],
                    ['key' => 'manager_email', 'label' => 'بريد المدير', 'required' => false, 'example' => 'manager@example.com'],
                    ['key' => 'bank_address', 'label' => 'عنوان البنك', 'required' => false, 'example' => 'Bank address'],
                    ['key' => 'bank_name', 'label' => 'اسم البنك', 'required' => false, 'example' => 'Bank name'],
                    ['key' => 'bank_account_name', 'label' => 'اسم صاحب الحساب', 'required' => false, 'example' => 'External Office'],
                    ['key' => 'bank_swift_code', 'label' => 'Swift Code', 'required' => false, 'example' => 'ABCDEFXX'],
                    ['key' => 'bank_iban', 'label' => 'IBAN', 'required' => false, 'example' => 'SA0000000000000000000000'],
                    ['key' => 'bank_account', 'label' => 'رقم الحساب البنكي', 'required' => false, 'example' => '1234567890'],
                    ['key' => 'cost_of_dollar', 'label' => 'تكلفة الدولار', 'required' => false, 'example' => '3.75'],
                    ['key' => 'cost_of_other', 'label' => 'تكلفة أخرى', 'required' => false, 'example' => '0'],
                ],
            ],
            'employees' => [
                'label' => 'الموظفين',
                'route_hint' => '/admin/employees',
                'icon' => 'fas fa-user-tie',
                'description' => 'إدخال أو تحديث بيانات موظفي الموارد البشرية. يمكن الربط بالقسم والوظيفة والجنسية والوردية باستخدام ID أو الاسم.',
                'filename' => 'employees-import-template.xlsx',
                'columns' => [
                    ['key' => 'id', 'label' => 'ID', 'required' => false, 'example' => '10'],
                    ['key' => 'name', 'label' => 'اسم الموظف', 'required' => true, 'example' => 'أحمد محمد علي'],
                    ['key' => 'code', 'label' => 'كود الموظف', 'required' => false, 'example' => 'EMP-001'],
                    ['key' => 'phone', 'label' => 'رقم الجوال', 'required' => true, 'example' => '0500000000'],
                    ['key' => 'email', 'label' => 'البريد الإلكتروني', 'required' => true, 'example' => 'employee@example.com'],
                    ['key' => 'national_num', 'label' => 'رقم الهوية/الإقامة', 'required' => true, 'example' => '1000000000'],
                    ['key' => 'identity_expiry_date', 'label' => 'تاريخ انتهاء الهوية', 'required' => false, 'example' => now()->addYear()->format('Y-m-d')],
                    ['key' => 'birthday', 'label' => 'تاريخ الميلاد', 'required' => true, 'example' => '1990-01-01'],
                    ['key' => 'nationality', 'label' => 'الجنسية', 'required' => true, 'example' => 'السعودية'],
                    ['key' => 'address', 'label' => 'العنوان', 'required' => false, 'example' => 'الرياض'],
                    ['key' => 'department', 'label' => 'القسم', 'required' => true, 'example' => 'الإدارة'],
                    ['key' => 'job', 'label' => 'الوظيفة', 'required' => true, 'example' => 'محاسب'],
                    ['key' => 'shift', 'label' => 'الوردية', 'required' => true, 'example' => 'دوام صباحي'],
                    ['key' => 'hired_date', 'label' => 'تاريخ التعيين', 'required' => true, 'example' => now()->format('Y-m-d')],
                    ['key' => 'contract_end_date', 'label' => 'تاريخ نهاية العقد', 'required' => true, 'example' => now()->addYear()->format('Y-m-d')],
                    ['key' => 'salary', 'label' => 'الراتب', 'required' => true, 'example' => '5000'],
                    ['key' => 'net_salary', 'label' => 'صافي الراتب', 'required' => false, 'example' => '5000'],
                    ['key' => 'status', 'label' => 'الحالة', 'required' => false, 'example' => 'نشط', 'valid_values' => ['نشط', 'غير نشط', 'active', 'inactive']],
                    ['key' => 'admin_id', 'label' => 'حساب المستخدم/الإدارة', 'required' => false, 'example' => '1'],
                    ['key' => 'maid_id', 'label' => 'ID السيرة المرتبطة', 'required' => false, 'example' => ''],
                ],
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
                    ['key' => 'visa_number', 'label' => 'رقم التأشيرة', 'required' => false, 'example' => 'VS-000001'],
                    ['key' => 'client_phone', 'label' => 'جوال العميل', 'required' => true, 'example' => '0500000000'],
                    ['key' => 'client_id_number', 'label' => 'رقم هوية العميل', 'required' => true, 'example' => '1000000000'],
                    ['key' => 'client_name', 'label' => 'اسم العميل', 'required' => false, 'example' => 'محمد أحمد'],
                    ['key' => 'maid_passport', 'label' => 'رقم جواز العاملة', 'required' => true, 'example' => 'P0000001'],
                    ['key' => 'nationality', 'label' => 'الجنسية', 'required' => false, 'example' => 'الفلبين'],
                    ['key' => 'contract_date', 'label' => 'تاريخ العقد', 'required' => true, 'example' => now()->format('Y-m-d')],
                    ['key' => 'arrival_date', 'label' => 'تاريخ الوصول', 'required' => false, 'example' => now()->format('Y-m-d')],
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
            'finance_constraints' => [
                'label' => 'القيود المالية',
                'route_hint' => '/finance/constraints',
                'icon' => 'fas fa-balance-scale',
                'description' => 'استيراد القيود المحاسبية. كل صف يمثل طرفا مدينا أو دائنا، ويتم تجميع الصفوف التي تحمل نفس رقم مجموعة القيد في قيد واحد. يجب تساوي إجمالي المدين والدائن.',
                'filename' => 'finance-constraints-import-template.xlsx',
                'columns' => [
                    ['key' => 'constraint_ref', 'label' => 'رقم مجموعة القيد', 'required' => true, 'example' => 'JV-0001'],
                    ['key' => 'code', 'label' => 'رقم القيد في النظام', 'required' => false, 'example' => '1001'],
                    ['key' => 'date', 'label' => 'تاريخ القيد', 'required' => true, 'example' => now()->format('Y-m-d')],
                    ['key' => 'type', 'label' => 'نوع القيد', 'required' => false, 'example' => 'daily_entry', 'valid_values' => ['daily_entry', 'first_balance', '1', '10']],
                    ['key' => 'statement', 'label' => 'بيان القيد', 'required' => true, 'example' => 'قيد افتتاحي مستورد'],
                    ['key' => 'line_type', 'label' => 'نوع السطر', 'required' => true, 'example' => 'debtor', 'valid_values' => ['debtor', 'creditor', 'مدين', 'دائن']],
                    ['key' => 'account_id', 'label' => 'Account ID', 'required' => false, 'example' => '15'],
                    ['key' => 'account_code', 'label' => 'كود الحساب', 'required' => false, 'example' => '110101'],
                    ['key' => 'account_name', 'label' => 'اسم الحساب', 'required' => false, 'example' => 'الصندوق الرئيسي'],
                    ['key' => 'amount', 'label' => 'المبلغ', 'required' => false, 'example' => '5000'],
                    ['key' => 'debtor_value', 'label' => 'قيمة المدين', 'required' => false, 'example' => '5000'],
                    ['key' => 'creditor_value', 'label' => 'قيمة الدائن', 'required' => false, 'example' => '0'],
                    ['key' => 'line_statement', 'label' => 'بيان السطر', 'required' => false, 'example' => 'طرف مدين من القيد'],
                    ['key' => 'cost_center_id', 'label' => 'Cost Center ID', 'required' => false, 'example' => ''],
                    ['key' => 'cost_center_code', 'label' => 'كود مركز التكلفة', 'required' => false, 'example' => ''],
                    ['key' => 'cost_center_name', 'label' => 'اسم مركز التكلفة', 'required' => false, 'example' => ''],
                ],
            ],
        ];

        $templates['recruitment_contracts_clients_maids'] = [
            'label' => 'عقود استقدام-عملاء-عاملات',
            'route_hint' => '/admin/RecruitmentContract',
            'icon' => 'fas fa-file-contract',
            'description' => 'قالب واحد لإنشاء أو تحديث بيانات العميل والعاملة وعقد الاستقدام من نفس الصف مع الحفاظ على القوالب القديمة.',
            'filename' => 'recruitment-contracts-clients-maids-import-template.xlsx',
            'columns' => $this->recruitmentBundleColumns(),
        ];

        $templates['rent_contracts_clients_rental_maids'] = [
            'label' => 'عقود ايجار-عملاء-سير ايجار',
            'route_hint' => '/admin/RentContract',
            'icon' => 'fas fa-file-invoice-dollar',
            'description' => 'قالب واحد لإنشاء أو تحديث بيانات العميل وسيرة الإيجار وعقد الإيجار من نفس الصف مع الحفاظ على القوالب القديمة.',
            'filename' => 'rent-contracts-clients-rental-maids-import-template.xlsx',
            'columns' => $this->rentBundleColumns(),
        ];

        return $templates;
    }

    public function template(string $type): ?array
    {
        return $this->templates()[$type] ?? null;
    }

    private function recruitmentBundleColumns(): array
    {
        return array_merge(
            $this->clientBundleColumns(),
            $this->maidBundleColumns(false),
            [
                ['key' => 'contract_number', 'label' => 'رقم عقد الاستقدام', 'required' => false, 'example' => 'RC-0001'],
                ['key' => 'visa_number', 'label' => 'رقم التأشيرة', 'required' => false, 'example' => 'VS-000001'],
                ['key' => 'contract_date', 'label' => 'تاريخ عقد الاستقدام', 'required' => true, 'example' => now()->format('Y-m-d')],
                ['key' => 'recruitment_contract_status', 'label' => 'حالة عقد الاستقدام', 'required' => true, 'example' => 'عقود مساند', 'valid_values' => $this->recruitmentContractStatusLabels()],
                ['key' => 'contract_amount', 'label' => 'قيمة عقد الاستقدام', 'required' => false, 'example' => '12000'],
                ['key' => 'contract_paid_amount', 'label' => 'المدفوع من عقد الاستقدام', 'required' => false, 'example' => '5000'],
                ['key' => 'contract_notes', 'label' => 'ملاحظات عقد الاستقدام', 'required' => false, 'example' => 'عقد مستورد من القالب المجمع'],
            ]
        );
    }

    private function rentBundleColumns(): array
    {
        return array_merge(
            $this->clientBundleColumns(),
            $this->maidBundleColumns(true),
            [
                ['key' => 'rent_contract_number', 'label' => 'رقم عقد الإيجار', 'required' => false, 'example' => 'RN-0001'],
                ['key' => 'rent_start_date', 'label' => 'تاريخ بداية الإيجار', 'required' => true, 'example' => now()->format('Y-m-d')],
                ['key' => 'rent_end_date', 'label' => 'تاريخ نهاية الإيجار', 'required' => false, 'example' => now()->addMonth()->format('Y-m-d')],
                ['key' => 'rent_duration_months', 'label' => 'مدة الإيجار بالأشهر', 'required' => false, 'example' => '1'],
                ['key' => 'rent_monthly_amount', 'label' => 'المبلغ الشهري للإيجار', 'required' => false, 'example' => '2500'],
                ['key' => 'rent_contract_status', 'label' => 'حالة عقد الإيجار', 'required' => true, 'example' => 'نشط', 'valid_values' => $this->rentalContractStatusLabels()],
                ['key' => 'rent_contract_notes', 'label' => 'ملاحظات عقد الإيجار', 'required' => false, 'example' => 'عقد إيجار مستورد من القالب المجمع'],
            ]
        );
    }

    private function clientBundleColumns(): array
    {
        return [
            ['key' => 'client_id', 'label' => 'ID العميل', 'required' => false, 'example' => '172'],
            ['key' => 'client_name', 'label' => 'اسم العميل', 'required' => true, 'example' => 'محمد أحمد علي'],
            ['key' => 'client_phone', 'label' => 'رقم جوال العميل', 'required' => true, 'example' => '0500000000'],
            ['key' => 'client_id_number', 'label' => 'رقم هوية/إقامة العميل', 'required' => true, 'example' => '1000000000'],
            ['key' => 'client_email', 'label' => 'بريد العميل الإلكتروني', 'required' => false, 'example' => 'client@example.com'],
            ['key' => 'client_nationality', 'label' => 'جنسية العميل', 'required' => false, 'example' => 'سعودي'],
            ['key' => 'client_birthdate', 'label' => 'تاريخ ميلاد العميل', 'required' => false, 'example' => '1990-01-01'],
            ['key' => 'client_marital_status', 'label' => 'الحالة الاجتماعية للعميل', 'required' => false, 'example' => 'متزوج', 'valid_values' => ['أعزب', 'متزوج', 'مطلق', 'أرمل', 'single', 'married', 'divorced', 'widowed']],
            ['key' => 'client_phone2', 'label' => 'جوال إضافي للعميل', 'required' => false, 'example' => '0550000000'],
            ['key' => 'client_category', 'label' => 'تصنيف العميل', 'required' => false, 'example' => 'أفراد'],
            ['key' => 'client_account_id', 'label' => 'Account ID العميل', 'required' => false, 'example' => '1001'],
            ['key' => 'client_city', 'label' => 'مدينة العميل', 'required' => false, 'example' => 'الرياض'],
            ['key' => 'client_address', 'label' => 'عنوان العميل', 'required' => false, 'example' => 'حي الياسمين'],
            ['key' => 'client_rooms_num', 'label' => 'عدد غرف العميل', 'required' => false, 'example' => '4'],
            ['key' => 'client_family_num', 'label' => 'عدد أفراد أسرة العميل', 'required' => false, 'example' => '5'],
            ['key' => 'client_children', 'label' => 'عدد أطفال العميل', 'required' => false, 'example' => '2'],
            ['key' => 'client_job', 'label' => 'وظيفة العميل', 'required' => false, 'example' => 'موظف'],
            ['key' => 'client_status', 'label' => 'حالة العميل', 'required' => false, 'example' => 'نشط'],
            ['key' => 'client_created_by', 'label' => 'أضيف العميل بواسطة', 'required' => false, 'example' => '1'],
            ['key' => 'client_notes', 'label' => 'ملاحظات العميل', 'required' => false, 'example' => 'عميل مستورد من القالب المجمع'],
        ];
    }

    private function maidBundleColumns(bool $rental): array
    {
        $columns = [
            ['key' => 'maid_code', 'label' => 'رقم السيرة', 'required' => false, 'example' => $rental ? 'RNT-001' : 'CV-001'],
            ['key' => 'maid_name_en', 'label' => 'اسم العاملة بالإنجليزية', 'required' => true, 'example' => 'EDIT_ME_NAME'],
            ['key' => 'maid_passport', 'label' => 'رقم جواز العاملة', 'required' => true, 'example' => 'P0000001'],
            ['key' => 'maid_nationality', 'label' => 'جنسية العاملة', 'required' => true, 'example' => $rental ? 'أوغندا' : 'الفلبين'],
            ['key' => 'maid_office', 'label' => 'مكتب الاستقدام', 'required' => $rental, 'example' => 'مكتب خارجي'],
            ['key' => 'maid_type', 'label' => 'نوع السيرة', 'required' => true, 'example' => $rental ? 'إيجار' : 'استقدام'],
            ['key' => 'maid_job_titles', 'label' => 'مهنة العاملة', 'required' => true, 'example' => 'عاملة منزلية'],
            ['key' => 'maid_religion', 'label' => 'ديانة العاملة', 'required' => false, 'example' => 'مسلمة'],
            ['key' => 'maid_request_type', 'label' => 'نوع طلب السيرة', 'required' => false, 'example' => 'حسب المواصفات'],
            ['key' => 'maid_salary', 'label' => 'راتب العاملة', 'required' => false, 'example' => '1500'],
            ['key' => 'maid_price', 'label' => 'تكلفة/سعر السيرة', 'required' => false, 'example' => $rental ? '2500' : '12000'],
            ['key' => 'maid_birth_date', 'label' => 'تاريخ ميلاد العاملة', 'required' => false, 'example' => '1998-01-01'],
            ['key' => 'maid_age', 'label' => 'عمر العاملة', 'required' => false, 'example' => '27'],
            ['key' => 'maid_height', 'label' => 'طول العاملة', 'required' => false, 'example' => '160'],
            ['key' => 'maid_weight', 'label' => 'وزن العاملة', 'required' => false, 'example' => '60'],
            ['key' => 'maid_id_number', 'label' => 'رقم هوية العاملة', 'required' => false, 'example' => 'EDIT-ID-001'],
            ['key' => 'maid_id_expiry_date', 'label' => 'تاريخ انتهاء هوية العاملة', 'required' => false, 'example' => now()->addYear()->format('Y-m-d')],
            ['key' => 'maid_marital_status', 'label' => 'الحالة الاجتماعية للعاملة', 'required' => false, 'example' => 'عازبة'],
            ['key' => 'maid_childrens_count', 'label' => 'عدد أطفال العاملة', 'required' => false, 'example' => '0'],
            ['key' => 'maid_duration', 'label' => 'مدة عقد العاملة', 'required' => false, 'example' => '24 شهر'],
            ['key' => 'maid_experience_status', 'label' => 'حالة خبرة العاملة', 'required' => true, 'example' => 'سبق لها العمل'],
            ['key' => 'maid_experience_years', 'label' => 'سنوات خبرة العاملة', 'required' => false, 'example' => '2'],
            ['key' => 'maid_skills', 'label' => 'مهارات العاملة', 'required' => false, 'example' => 'طبخ|تنظيف'],
            ['key' => 'maid_languages', 'label' => 'لغات العاملة', 'required' => false, 'example' => 'arabic|english'],
            ['key' => 'maid_language_percentages', 'label' => 'نسب لغات العاملة', 'required' => false, 'example' => 'arabic:90|english:70'],
            ['key' => 'maid_status', 'label' => 'حالة توفر العاملة', 'required' => false, 'example' => 'متاحة'],
            ['key' => 'maid_available_at', 'label' => 'متاحة من', 'required' => false, 'example' => now()->format('Y-m-d')],
            ['key' => 'maid_arrival_date', 'label' => 'تاريخ وصول العاملة', 'required' => false, 'example' => now()->format('Y-m-d')],
            ['key' => 'maid_create_shelter_contract', 'label' => 'إنشاء عقد إيواء للعاملة', 'required' => false, 'example' => 'لا'],
            ['key' => 'maid_is_show', 'label' => 'إظهار السيرة في الموقع', 'required' => false, 'example' => 'نعم'],
            ['key' => 'maid_is_pinned', 'label' => 'تثبيت السيرة', 'required' => false, 'example' => 'لا'],
            ['key' => 'maid_video_link', 'label' => 'رابط فيديو العاملة', 'required' => false, 'example' => 'https://example.com/video'],
            ['key' => 'maid_notes', 'label' => 'ملاحظات العاملة', 'required' => false, 'example' => 'سيرة مستوردة من القالب المجمع'],
        ];

        if ($rental) {
            $columns[] = ['key' => 'maid_finance_income', 'label' => 'إيرادات تأجير العاملة', 'required' => false, 'example' => '0'];
            $columns[] = ['key' => 'maid_finance_expense', 'label' => 'مصروفات تأجير العاملة', 'required' => false, 'example' => '0'];
        }

        return $columns;
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
        $exampleRows = $type === 'finance_constraints' ? $this->financeConstraintExampleRows() : null;
        $metadataRow = $exampleRows ? 4 : 3;

        foreach ($template['columns'] as $index => $column) {
            $columnIndex = $index + 1;
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->setCellValue("{$columnLetter}1", $column['label']);
            if ($exampleRows) {
                $sheet->setCellValue("{$columnLetter}2", $exampleRows[0][$column['key']] ?? ($column['example'] ?? ''));
                $sheet->setCellValue("{$columnLetter}3", $exampleRows[1][$column['key']] ?? '');
            } else {
                $sheet->setCellValue("{$columnLetter}2", $column['example'] ?? '');
            }
            $sheet->setCellValue("{$columnLetter}{$metadataRow}", $column['required'] ? 'مطلوب' : 'اختياري');
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($template['columns']));
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
        ]);
        $sheet->getStyle("A2:{$lastColumn}{$metadataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('A2');

        foreach (range(1, count($template['columns'])) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('تعليمات');
        $instructions->setRightToLeft(true);
        $instructions->setCellValue('A1', $template['label']);
        $instructions->setCellValue('A2', $template['description']);
        $instructions->setCellValue('A3', $exampleRows
            ? 'لا تغير أسماء الأعمدة. الصفان الثاني والثالث مثال لقيد متوازن، واحذف أو عدل البيانات قبل الرفع.'
            : 'لا تغير أسماء الأعمدة. استخدم الصف الثاني كمثال، واحذف أو عدل البيانات قبل الرفع.');
        $instructions->setCellValue('A4', $exampleRows
            ? 'الصف الرابع يوضح هل الحقل مطلوب أو اختياري.'
            : 'الصف الثالث يوضح هل الحقل مطلوب أو اختياري.');

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

    private function financeConstraintExampleRows(): array
    {
        return [
            [
                'constraint_ref' => 'JV-0001',
                'code' => '',
                'date' => now()->format('Y-m-d'),
                'type' => 'daily_entry',
                'statement' => 'قيد افتتاحي مستورد',
                'line_type' => 'debtor',
                'account_id' => '',
                'account_code' => '110101',
                'account_name' => 'الصندوق الرئيسي',
                'amount' => '5000',
                'debtor_value' => '5000',
                'creditor_value' => '0',
                'line_statement' => 'طرف مدين من القيد',
                'cost_center_id' => '',
                'cost_center_code' => '',
                'cost_center_name' => '',
            ],
            [
                'constraint_ref' => 'JV-0001',
                'code' => '',
                'date' => now()->format('Y-m-d'),
                'type' => 'daily_entry',
                'statement' => 'قيد افتتاحي مستورد',
                'line_type' => 'creditor',
                'account_id' => '',
                'account_code' => '210101',
                'account_name' => 'حساب دائن',
                'amount' => '5000',
                'debtor_value' => '0',
                'creditor_value' => '5000',
                'line_statement' => 'طرف دائن من القيد',
                'cost_center_id' => '',
                'cost_center_code' => '',
                'cost_center_name' => '',
            ],
        ];
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
