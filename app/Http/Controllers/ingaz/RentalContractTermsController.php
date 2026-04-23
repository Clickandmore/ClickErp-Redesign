<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\RentalContractTerm;
use App\Models\Setting;
use Illuminate\Http\Request;

class RentalContractTermsController extends Controller
{
    /**
     * عرض صفحة إدارة البنود
     */

    public function index()
    {
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
        $termsPage1 = RentalContractTerm::byPage(1)->ordered()->get();
        $termsPage2 = RentalContractTerm::byPage(2)->ordered()->get();
        $termsPage3 = RentalContractTerm::byPage(3)->ordered()->get();

        return view('admin.ingaz.RentContractTerms.create', compact('termsPage1', 'termsPage2', 'termsPage3'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedTermData($request);

        RentalContractTerm::create($data);

        return redirect()
            ->route('rent-contract-terms.index')
            ->with('success', 'تم إضافة بند العقد بنجاح');
    }

    public function edit($id)
    {
        $termsPage1 = RentalContractTerm::byPage(1)->ordered()->get();
        $termsPage2 = RentalContractTerm::byPage(2)->ordered()->get();
        $termsPage3 = RentalContractTerm::byPage(3)->ordered()->get();
        $term = RentalContractTerm::findorfail($id);
        return view('admin.ingaz.RentContractTerms.edit', compact('termsPage1', 'term', 'termsPage2', 'termsPage3'));
    }

    /**
     * تحديث البنود
     */
    public function update(Request $request, $id)
    {
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
        // حذف البنود الحالية
        RentalContractTerm::truncate();

        // البنود الافتراضية
        $defaultTerms = [
            // الصفحة الأولى
            [
                'section_title' => 'أولاً: موضوع العقد',
                'section_order' => 1,
                'term_number' => null,
                'term_content' => 'يقر الطرف الأول بأنه يزاول نشاط تقديم خدمات العمالة المنزلية بموجب ترخيص رقم -------------- ويقدم الطرف الأول خدمات العمالة المنزلية للطرف الثاني بناء على طلبه وفقا للشروط والأحكام الواردة في هذا العقد.',
                'term_order' => 1,
                'page_number' => 1,
                'is_active' => true
            ],

            // الصفحة الثانية
            [
                'section_title' => 'ثانياً: مدة العقد',
                'section_order' => 2,
                'term_number' => null,
                'term_content' => 'مدة العقد -------------- يوما على أن تبدأ من -------------- قابلة للتجديد لمدة أو مدد مماثلة بموجب أشعار مكتوب من الطرف الثاني للطرف الأول قبل نهاية',
                'term_order' => 1,
                'page_number' => 2,
                'is_active' => true
            ],
            [
                'section_title' => 'ثالثاً: قيمة العقد',
                'section_order' => 3,
                'term_number' => 1,
                'term_content' => 'يدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العاملة المنزلية: وفقا للتكلفة الشهرية (------) المعلنة --- في تاريخ التعاقد: وفقا لما يلي:',
                'term_order' => 1,
                'page_number' => 2,
                'is_active' => true
            ],
            [
                'section_title' => 'ثالثاً: قيمة العقد',
                'section_order' => 3,
                'term_number' => 3,
                'term_content' => 'في حال تجديد هذا العقد، ومرافقة العامل على الاستمرار في العمل لدى الطرف الثاني، يدفع الطرف الثاني للطرف الأول تكلفة مقابل تقديم خدمات العامل المنزلي للمدة الجديدة تعادل التكلفة السابقة.',
                'term_order' => 2,
                'page_number' => 2,
                'is_active' => true
            ],
            [
                'section_title' => 'رابعا: التزامات الطرف الأول',
                'section_order' => 4,
                'term_number' => 1,
                'term_content' => 'أن يقدم للطرف الثاني عاملة منزلية ذو كفاءة وخبرة بمهنة خدمة المنازل.',
                'term_order' => 1,
                'page_number' => 2,
                'is_active' => true
            ],
            [
                'section_title' => 'رابعا: التزامات الطرف الأول',
                'section_order' => 4,
                'term_number' => 2,
                'term_content' => 'إعداد العامل وتهيئته للعمل، والتأكد من جاهزيته لتقديم الخدمة.',
                'term_order' => 2,
                'page_number' => 2,
                'is_active' => true
            ],
            [
                'section_title' => 'رابعا: التزامات الطرف الأول',
                'section_order' => 4,
                'term_number' => 3,
                'term_content' => 'تسليم العامل للطرف الثاني في الوقت والمكان المتفق عليه.',
                'term_order' => 3,
                'page_number' => 2,
                'is_active' => true
            ],
            [
                'section_title' => 'رابعا: التزامات الطرف الأول',
                'section_order' => 4,
                'term_number' => 4,
                'term_content' => 'استبدال العامل في حال عدم كفاءته، أو رغبة الطرف الثاني في استبداله خلال (24) ساعة.',
                'term_order' => 4,
                'page_number' => 2,
                'is_active' => true
            ],

            // يمكنك إضافة باقي البنود هنا...
        ];

        // إدراج البنود الافتراضية
        foreach ($defaultTerms as $term) {
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
            'logo'
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
        $data = $request->except(['_token', '_method', 'logo']);

        // حفظ الإعدادات
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => 'rent_contract_' . $key],
                ['value' => $value]
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
        return jsonSuccess(['url' => route('rent-contract.settings')], __("تم حفظ الإعدادات بنجاح"), 201);
    }

    public function destroy($id)
    {
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
            'page_number' => ['required', 'integer', 'in:1,2,3'],
            'term_content' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['section_order'] = $data['section_order'] ?? 1;
        $data['term_order'] = $data['term_order'] ?? 1;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
