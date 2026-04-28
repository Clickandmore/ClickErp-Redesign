<?php

namespace App\Http\Controllers\admin;

use App\Http\Actions\dashboard\SettingAction;
use App\Http\Requests\ingaz\SettingRequest;
use App\Models\ingaz\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class FormSettingsController extends Controller
{
    private array $formSettingFields = [
        'document_logo',
        'zatch_logo',
        'qr_image',
        'second_qr_image',
        'tax_number',
        'phone',
        'other_phone',
        'email',
        'location:ar',
        'location:en',
        'document_company_name:ar',
        'document_company_name:en',
        'document_office_name:ar',
        'document_office_name:en',
        'document_footer_note:ar',
        'document_footer_note:en',
        'delivery_form_title:ar',
        'delivery_form_title:en',
        'delivery_form_default_text:ar',
        'delivery_form_default_text:en',
        'shelter_receipt_title:ar',
        'shelter_receipt_title:en',
        'shelter_receipt_subtitle:ar',
        'shelter_receipt_subtitle:en',
        'shelter_receipt_default_text:ar',
        'shelter_receipt_default_text:en',
        'shelter_receipt_default_settlement_text:ar',
        'shelter_receipt_default_settlement_text:en',
        'invoice_title:ar',
        'invoice_title:en',
        'invoice_subtitle:ar',
        'invoice_subtitle:en',
        'invoice_footer_text:ar',
        'invoice_footer_text:en',
        'rental_contract_title:ar',
        'rental_contract_title:en',
        'rental_contract_intro_text:ar',
        'rental_contract_intro_text:en',
    ];

    public function index()
    {
        $settings = Setting::query()->first() ?: Setting::query()->create();

        return view('admin.form-settings.index', [
            'settings' => $settings,
            'templates' => $this->templates(),
            'previewLocales' => $this->previewLocales(),
        ]);
    }

    public function update(SettingRequest $request, SettingAction $action)
    {
        $setting = Setting::query()->first() ?: Setting::query()->create();
        $data = Arr::only($request->all(), $this->formSettingFields);

        $action->updateSetting($setting->id, $data);

        return jsonSuccess([
            'redirect' => route('form-settings.index'),
        ], null, 200);
    }

    public function preview(Request $request, string $template)
    {
        $definition = $this->templates()[$template] ?? null;
        abort_if(!$definition, 404);

        app()->setLocale($this->resolveLocale($request));

        return response()->view(
            $definition['view'],
            $this->buildPreviewData($template),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    public function print(Request $request, string $template)
    {
        $definition = $this->templates()[$template] ?? null;
        abort_if(!$definition, 404);

        return view('admin.form-settings.print-frame', [
            'title' => $definition['title'],
            'src' => route('form-settings.preview', [
                'template' => $template,
                'locale' => $this->resolveLocale($request),
            ]),
        ]);
    }

    private function templates(): array
    {
        return [
            'delivery-form' => [
                'key' => 'delivery-form',
                'title' => 'نموذج تسليم العمالة',
                'description' => 'نموذج استلام العاملة مع إقرار العميل والتوقيع النهائي.',
                'view' => 'admin.ingaz.RecruitmentContract.delivery_form_print',
                'fields' => [
                    'عنوان النموذج',
                    'النص الافتراضي',
                ],
            ],
            'shelter-receipt' => [
                'key' => 'shelter-receipt',
                'title' => 'نموذج الإيواء',
                'description' => 'نموذج الاستلام والتعهد المستخدم في حالات الإيواء.',
                'view' => 'admin.ingaz.ShelterContract.receipt_form_print',
                'fields' => [
                    'عنوان النموذج',
                    'العنوان الفرعي',
                    'نص التعهد',
                    'نص المخالصة',
                ],
            ],
            'invoice' => [
                'key' => 'invoice',
                'title' => 'الفاتورة',
                'description' => 'معاينة نموذج الفاتورة الضريبية المبسطة ببيانات افتراضية.',
                'view' => 'admin.ingaz.invoices.print',
                'fields' => [
                    'عنوان الفاتورة',
                    'العنوان الفرعي',
                    'تذييل الفاتورة',
                ],
            ],
            'rental-contract' => [
                'key' => 'rental-contract',
                'title' => 'عقد الإيجار',
                'description' => 'معاينة العقد مع مقدمة العقد والعنوان المخصصين للطباعة.',
                'view' => 'admin.print.rental-contract-dynamic',
                'fields' => [
                    'عنوان العقد',
                    'مقدمة العقد',
                ],
            ],
        ];
    }

    private function previewLocales(): array
    {
        return collect(config('translatable.locales', ['ar']))
            ->filter(fn ($locale) => in_array($locale, ['ar', 'en'], true))
            ->values()
            ->all();
    }

    private function resolveLocale(Request $request): string
    {
        $locale = (string) $request->query('locale', app()->getLocale());

        return in_array($locale, $this->previewLocales(), true) ? $locale : app()->getLocale();
    }

    private function buildPreviewData(string $template): array
    {
        return match ($template) {
            'delivery-form' => $this->deliveryFormPreviewData(),
            'shelter-receipt' => $this->shelterReceiptPreviewData(),
            'invoice' => $this->invoicePreviewData(),
            'rental-contract' => $this->rentalContractPreviewData(),
            default => [],
        };
    }

    private function deliveryFormPreviewData(): array
    {
        $today = Carbon::now();
        $company = documentBranding();

        return [
            'deliveryCompanyName' => $company['company_name'],
            'deliveryOfficeName' => $company['office_name'],
            'client_name' => 'أحمد محمد السبيعي',
            'national_identification_number' => '1023456789',
            'client_phone' => '+966 50 123 4567',
            'client_address' => 'الرياض - حي الياسمين - شارع العليا',
            'passport_id' => 'P4582176',
            'nationality_id' => 'أوغندا',
            'receipt_day_name' => $today->locale(app()->getLocale())->translatedFormat('l'),
            'date_receipt' => $today->format('Y/m/d'),
            'text_receipt' => '',
            'company_representative' => 'سارة خالد',
            'document_footer_note' => $company['footer_note'],
            'print_url' => route('form-settings.preview.print', ['template' => 'delivery-form', 'locale' => app()->getLocale()]),
            'pdf_url' => route('form-settings.preview.print', ['template' => 'delivery-form', 'locale' => app()->getLocale()]),
        ];
    }

    private function shelterReceiptPreviewData(): array
    {
        $company = documentBranding();
        $today = Carbon::now();

        return [
            'document_company_name' => $company['company_name'],
            'document_office_name' => $company['office_name'],
            'document_footer_note' => $company['footer_note'],
            'client_name' => 'ريم عبدالله القحطاني',
            'national_identification_number' => '1087654321',
            'passport_id' => 'N2047781',
            'nationality_id' => 'كينيا',
            'arrive_date' => $today->copy()->subDays(6)->format('Y/m/d'),
            'date_receipt' => $today->format('Y/m/d'),
            'cvs_name' => 'Grace Njeri',
            'text_receipt' => '',
            'printed_by' => auth('admin')->user()?->name ?? 'مدير النظام',
            'printed_at' => $today->format('Y/m/d H:i:s'),
            'contract_signed_at' => $today->copy()->subDays(2)->format('Y/m/d H:i'),
            'print_url' => route('form-settings.preview.print', ['template' => 'shelter-receipt', 'locale' => app()->getLocale()]),
        ];
    }

    private function invoicePreviewData(): array
    {
        $settings = Setting::query()->first();
        $today = Carbon::now();
        $zatcaLogo = ($settings && $settings->zatch_logo) ? showFile($settings->zatch_logo) : null;
        $qrImage = ($settings && $settings->qr_image) ? showFile($settings->qr_image) : null;
        $secondQrImage = ($settings && $settings->second_qr_image) ? showFile($settings->second_qr_image) : null;

        return [
            'invoice' => (object) ['id' => 10045],
            'settings' => $settings,
            'supplierName' => (string) documentSetting('document_company_name', documentSetting('website_name', config('app.name'))),
            'supplierAddress' => (string) documentSetting('location', 'الرياض، المملكة العربية السعودية'),
            'supplierPhone' => $this->printableSetting('phone', '+966 11 234 5678'),
            'supplierOtherPhone' => $this->printableSetting('other_phone', '+966 55 765 4321'),
            'supplierEmail' => $this->printableSetting('email', 'info@clickerp.test'),
            'supplierTaxNumber' => $this->printableSetting('tax_number', '314232024200003'),
            'supplierLogo' => documentLogoUrl(asset('admin/assets/img/company_logo.png')),
            'invoiceTitle' => (string) documentSetting('invoice_title', 'فاتورة ضريبية مبسطة'),
            'invoiceSubtitle' => (string) documentSetting('invoice_subtitle', 'Simplified Tax Invoice'),
            'invoiceFooterText' => (string) replaceDocumentPlaceholders(documentSetting('invoice_footer_text', '')),
            'zatcaLogo' => $zatcaLogo ?: asset('site/assets/images/zatca-logo.png'),
            'qrImage' => $qrImage ?: ($secondQrImage ?: asset('site/assets/images/qr1.png')),
            'serviceDesc' => 'طلب تعاقد',
            'serviceLongDescription' => 'فاتورة تجريبية لمعاينة شكل الطباعة والنصوص الافتراضية قبل اعتمادها على الفواتير الحقيقية.',
            'items' => [
                [
                    'label' => 'تكاليف الاستقدام المباشر',
                    'unit' => 8500,
                    'qty' => 1,
                    'line_subtotal' => 8500,
                    'line_discount' => 0,
                    'line_total' => 8500,
                    'vat_rate' => 0.15,
                    'vat_amount' => 1275,
                    'line_grand_total' => 9775,
                ],
                [
                    'label' => 'تكلفة التذكرة الداخلية',
                    'unit' => 1200,
                    'qty' => 1,
                    'line_subtotal' => 1200,
                    'line_discount' => 0,
                    'line_total' => 1200,
                    'vat_rate' => 0.15,
                    'vat_amount' => 180,
                    'line_grand_total' => 1380,
                ],
            ],
            'invoiceStatus' => 'غير مدفوعة',
            'printedBy' => auth('admin')->user()?->name ?? 'مدير النظام',
            'printedAt' => $today,
            'customerName' => 'شركة المدى التجارية',
            'customerNationalId' => '7001234567',
            'customerPhone' => '+966 53 222 3344',
            'customerAddress' => 'الدمام - حي الفيصلية',
            'accountNumber' => 'ACC-20031',
            'matchedHijriDate' => $today->format('Y-m-d'),
            'invoiceDate' => $today->format('Y-m-d'),
            'invoiceDateTime' => $today->format('Y-m-d H:i:s'),
            'invoiceUrl' => route('form-settings.preview', ['template' => 'invoice', 'locale' => app()->getLocale()]),
            'subtotalBeforeDiscount' => 9700,
            'discountTotal' => 0,
            'netSubtotal' => 9700,
            'vatAmount' => 1455,
            'grandTotal' => 11155,
        ];
    }

    private function rentalContractPreviewData(): array
    {
        $today = Carbon::now();

        $contract = (object) [
            'id' => 9001,
            'signature' => null,
            'contract_date' => $today->format('Y-m-d'),
            'user' => (object) [
                'name' => 'محمد علي الدوسري',
                'iqama_number' => '1012345678',
                'address' => 'الرياض - حي النرجس',
                'phone' => '0551234567',
                'nationality' => (object) [
                    'name' => 'سعودي',
                ],
            ],
            'maid' => (object) [
                'name' => 'Martha Atieno',
                'passport_num' => 'A9087765',
                'experience' => '3 سنوات',
                'nationality' => (object) [
                    'name' => 'كينيا',
                ],
            ],
            'info' => (object) [
                'contract_duration' => 90,
                'contract_date' => $today->format('Y-m-d'),
                'monthly_cost' => 2600,
                'total_cost' => 7800,
                'working_hours' => 8,
            ],
        ];

        return [
            'contract' => $contract,
            'contractSettings' => [
                'company_name' => documentSetting('document_company_name', documentSetting('website_name', config('app.name'))),
                'license_number' => '4001772',
                'license_date' => '2024/10/13',
                'commercial_register' => '1010601958',
                'commercial_date' => '2024-03-03',
                'address' => documentSetting('location', 'الرياض'),
                'phone' => documentSetting('phone', '+966 11 234 5678'),
                'email' => documentSetting('email', 'info@clickerp.test'),
                'fax' => '+966 11 222 1111',
            ],
            'termsPage1' => collect([
                (object) [
                    'section_title' => 'أولاً: موضوع العقد',
                    'term_number' => 1,
                    'term_content' => 'يلتزم الطرف الأول بتوفير العاملة المنزلية للطرف الثاني خلال مدة العقد الموضحة في هذا النموذج، وذلك وفق البيانات والمقابل المالي المتفق عليهما.',
                ],
                (object) [
                    'section_title' => 'ثانياً: مدة العقد والأجر',
                    'term_number' => 2,
                    'term_content' => 'مدة العقد [مدة_العقد] يوماً تبدأ من [تاريخ_البداية]، ويكون الأجر الشهري [التكلفة_الشهرية] ريال بإجمالي [إجمالي_التكلفة] ريال.',
                ],
            ]),
            'termsPage2' => collect([
                (object) [
                    'section_title' => 'ثالثاً: بيانات العاملة',
                    'term_number' => 3,
                    'term_content' => 'اسم العاملة [اسم_العاملة] وجنسيتها [جنسية_العاملة] ورقم جوازها [رقم_الجواز]، ويلتزم الطرف الثاني باستلامها في الموعد المحدد.',
                ],
            ]),
            'termsPage3' => collect([
                (object) [
                    'section_title' => 'رابعاً: أحكام عامة',
                    'term_number' => 4,
                    'term_content' => 'يُعمل بهذا العقد بعد توقيع الطرفين عليه، وتطبق عليه الأنظمة والتعليمات المعمول بها في المملكة العربية السعودية.',
                ],
            ]),
            'allowSignature' => true,
        ];
    }

    private function printableSetting(string $key, string $default = '-'): string
    {
        $value = trim((string) documentSetting($key, ''));

        return $value === '' || $value === '#' ? $default : $value;
    }
}
