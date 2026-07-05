<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\RentalContractTerm;
use App\Models\Setting;
use App\PermKeys;
use App\Support\RentalContractDefaultTerms;
use Illuminate\Http\Request;

class RentalContractTermsController extends Controller
{
    /**
     * عرض صفحة إدارة البنود
     */

    public function index()
    {
        $this->authorizeTermsModuleAccess();

        $terms = RentalContractTerm::query()
            ->orderBy('page_number')
            ->ordered()
            ->get();
        $data["createRoute"] = route('rent-contract-terms.create');
        $data["dataTableRoute"] = route('rent-contract-terms.index');
        $data["bladeTitle"] = helperTrans('api.services');
        $data["addButtonText"] = helperTrans('api.service');
        $data["modalType"] = "modal-lg";
        return view('admin.ingaz.RentContractTerms.index', compact('data'))->with(['terms' => $terms]);
    }

    /**
     * عرض صفحة تعديل البنود
     */
    public function create()
    {
        $this->authorizeTermsCreate();

        $sectionTitles = $this->sectionTitles();
        $nextSectionOrder = ((int) RentalContractTerm::max('section_order')) + 1;
        $nextTermOrder = ((int) RentalContractTerm::where('page_number', old('page_number', 1))->max('term_order')) + 1;

        return view('admin.ingaz.RentContractTerms.create', compact('sectionTitles', 'nextSectionOrder', 'nextTermOrder'));
    }

    public function store(Request $request)
    {
        $this->authorizeTermsCreate();

        $data = $this->validatedTermData($request);

        RentalContractTerm::create($data);

        return redirect()
            ->route('rent-contract-terms.index')
            ->with('success', 'تم إضافة بند العقد بنجاح');
    }

    public function edit($id)
    {
        $this->authorizeTermsEdit();

        $term = RentalContractTerm::findorfail($id);
        $sectionTitles = $this->sectionTitles();

        return view('admin.ingaz.RentContractTerms.edit', compact('term', 'sectionTitles'));
    }

    /**
     * تحديث البنود
     */
    public function update(Request $request, $id)
    {
        $this->authorizeTermsEdit();

        $rentalContractTerm = RentalContractTerm::findOrFail($id);
        $rentalContractTerm->update($this->validatedTermData($request));

        return redirect()->route('rent-contract-terms.index')
            ->with('success', 'تم تحديث بنود العقد بنجاح');
    }

    /**
     * استيراد البنود الافتراضية
     */
    public function importDefault()
    {
        $this->authorizeTermsEdit();

        // حذف البنود الحالية
        RentalContractTerm::truncate();

        // إدراج البنود الافتراضية
        foreach (RentalContractDefaultTerms::all() as $term) {
            RentalContractTerm::create($term);
        }

        return redirect()->route('rent-contract-terms.index')
            ->with('success', 'تم استيراد البنود الافتراضية بنجاح');
    }

    /**
     * عرض صفحة إعدادات العقد
     */
    public function showSettings()
    {
        abort_unless(\AdminGuardHasPerm(PermKeys::CONTRACT_SETTINGS), 403);

        // جلب الإعدادات الحالية
        $settings = [];
        $settingKeys = [
            'company_name',
            'license_number',
            'license_date',
            'commercial_register',
            'commercial_date',
            'city',
            'address',
            'po_box',
            'postal_code',
            'phone',
            'mobile',
            'fax',
            'email',
            'default_trial_period',
            'default_working_hours',
            'daily_deduction',
            'notice_period',
            'expiry_reminder_enabled',
            'expiry_reminder_days',
            'expiry_reminder_channel',
            'logo',
            'company_signature'
        ];

        foreach ($settingKeys as $key) {
            $setting = Setting::where('key', 'rent_contract_' . $key)->first();
            $settings[$key] = $setting ? $setting->value : null;
        }

        return view('admin.ingaz.RentContract.settings', compact('settings'));
    }

    /**
     * تحديث إعدادات العقد
     */
    public function updateSettings(Request $request)
    {
        abort_unless(\AdminGuardHasPerm(PermKeys::CONTRACT_SETTINGS), 403);

        $request->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'company_signature' => ['nullable', 'image', 'max:2048'],
            'expiry_reminder_enabled' => ['nullable', 'in:0,1'],
            'expiry_reminder_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'expiry_reminder_channel' => ['nullable', 'in:sms,whatsapp,both'],
        ]);

        $data = $request->except(['_token', '_method', 'logo', 'company_signature']);

        // حفظ الإعدادات
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => 'rent_contract_' . $key],
                ['value' => $value ?? '']
            );
        }

        // معالجة الشعار إذا تم رفعه
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoPath = $logo->store('contract_logos', 'public');

            Setting::updateOrCreate(
                ['key' => 'rent_contract_logo'],
                ['value' => $logoPath]
            );
        }

        if ($request->hasFile('company_signature')) {
            $signature = $request->file('company_signature');
            $signaturePath = $signature->store('contract_signatures', 'public');

            Setting::updateOrCreate(
                ['key' => 'rent_contract_company_signature'],
                ['value' => $signaturePath]
            );
        }
        return jsonSuccess(['url' => route('rent-contract.settings')], __("تم حفظ الإعدادات بنجاح"), 201);
    }

    public function destroy($id)
    {
        $this->authorizeTermsDelete();

        $rent_contract_term = RentalContractTerm::findOrFail($id);
        $rent_contract_term->delete();

        return response()->json(
            ['success' => true, 'message' => 'تم حذف البند بنجاح.'],
            200
        );
    }

    private function validatedTermData(Request $request): array
    {
        $data = $request->validate([
            'section_title' => ['required', 'string', 'max:255'],
            'term_number' => ['nullable', 'integer', 'min:1'],
            'section_order' => ['nullable', 'integer', 'min:1'],
            'term_order' => ['nullable', 'integer', 'min:1'],
            'page_number' => ['required', 'integer', 'min:1'],
            'term_content' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['section_order'] = $data['section_order'] ?? $this->nextSectionOrder($data['section_title'], (int) $data['page_number']);
        $data['term_order'] = $data['term_order'] ?? $this->nextTermOrder($data['section_title'], (int) $data['page_number']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function sectionTitles()
    {
        return RentalContractTerm::query()
            ->select('section_title', 'section_order')
            ->orderBy('section_order')
            ->orderBy('section_title')
            ->get()
            ->pluck('section_title')
            ->filter()
            ->unique()
            ->values();
    }

    private function nextSectionOrder(string $sectionTitle, int $pageNumber): int
    {
        $existingSectionOrder = RentalContractTerm::query()
            ->where('page_number', $pageNumber)
            ->where('section_title', $sectionTitle)
            ->orderBy('section_order')
            ->value('section_order');

        if ($existingSectionOrder) {
            return (int) $existingSectionOrder;
        }

        return ((int) RentalContractTerm::max('section_order')) + 1;
    }

    private function nextTermOrder(string $sectionTitle, int $pageNumber): int
    {
        return ((int) RentalContractTerm::query()
            ->where('page_number', $pageNumber)
            ->where('section_title', $sectionTitle)
            ->max('term_order')) + 1;
    }

    private function authorizeTermsModuleAccess(): void
    {
        abort_unless(\AdminGuardHasAnyPerm([
            PermKeys::RENT_CONTRACT_TERMS_VIEW,
            PermKeys::RENT_CONTRACT_TERMS_CREATE,
            PermKeys::RENT_CONTRACT_TERMS_EDIT,
            PermKeys::RENT_CONTRACT_TERMS_DELETE,
            PermKeys::RENT_CONTRACT_VIEW,
            PermKeys::RENT_CONTRACT_CREATE,
            PermKeys::RENT_CONTRACT_EDIT,
            PermKeys::RENT_CONTRACT_DELETE,
            PermKeys::RENT_CONTRACT_CONTROL,
            PermKeys::CONTRACT_SETTINGS,
        ]), 403);
    }

    private function authorizeTermsCreate(): void
    {
        abort_unless(\AdminGuardHasAnyPerm([
            PermKeys::RENT_CONTRACT_TERMS_CREATE,
            PermKeys::RENT_CONTRACT_CREATE,
            PermKeys::CONTRACT_SETTINGS,
        ]), 403);
    }

    private function authorizeTermsEdit(): void
    {
        abort_unless(\AdminGuardHasAnyPerm([
            PermKeys::RENT_CONTRACT_TERMS_EDIT,
            PermKeys::RENT_CONTRACT_EDIT,
            PermKeys::CONTRACT_SETTINGS,
        ]), 403);
    }

    private function authorizeTermsDelete(): void
    {
        abort_unless(\AdminGuardHasAnyPerm([
            PermKeys::RENT_CONTRACT_TERMS_DELETE,
            PermKeys::RENT_CONTRACT_DELETE,
            PermKeys::CONTRACT_SETTINGS,
        ]), 403);
    }
}
