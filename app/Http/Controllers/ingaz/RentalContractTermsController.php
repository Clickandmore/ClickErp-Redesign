<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\RentalContractTerm;
use App\Models\ingaz\Setting;
use Illuminate\Http\Request;
use DB;

class RentalContractTermsController extends Controller
{
    /**
     * عرض صفحة إدارة البنود
     */
    public function index()
    {
        $terms = RentalContractTerm::ordered()->get()->groupBy('section_title');
        return view('admin.ingaz.RentContract.terms.index', compact('terms'));
    }

    /**
     * عرض صفحة تعديل البنود
     */
    public function edit()
    {
        $termsPage1 = RentalContractTerm::byPage(1)->ordered()->get();
        $termsPage2 = RentalContractTerm::byPage(2)->ordered()->get();
        $termsPage3 = RentalContractTerm::byPage(3)->ordered()->get();

        return view('admin.ingaz.RentContract.terms.edit', compact('termsPage1', 'termsPage2', 'termsPage3'));
    }

    /**
     * تحديث البنود
     */
        public function update(Request $request)
    {
        // حذف جميع البنود القديمة
        RentalContractTerm::truncate();

        // إضافة البنود الجديدة
        if ($request->has('terms')) {
            foreach ($request->terms as $term) {
                if (!empty($term['term_content'])) {
                    RentalContractTerm::create([
                        'section_title' => $term['section_title'],
                        'section_order' => $term['section_order'] ?? 1,
                        'term_number' => $term['term_number'] ?? null,
                        'term_content' => $term['term_content'],
                        'term_order' => $term['term_order'] ?? 1,
                        'page_number' => $term['page_number'] ?? 1,
                        'is_active' => isset($term['is_active']) ? true : false
                    ]);
                }
            }
        }

        return redirect()->route('rent-contract-terms.edit')
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

            return redirect()->route('rent-contract-terms.edit')
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
            'company_name', 'license_number', 'license_date', 'commercial_register',
            'commercial_date', 'city', 'address', 'po_box', 'postal_code',
            'phone', 'mobile', 'fax', 'email', 'default_trial_period',
            'default_working_hours', 'daily_deduction', 'notice_period', 'logo'
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

        return redirect()->route('rent-contract.settings')
                       ->with('success', 'تم حفظ الإعدادات بنجاح');
    }
}
