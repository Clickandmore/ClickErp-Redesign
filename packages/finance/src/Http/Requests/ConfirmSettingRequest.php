<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use App\Support\RecruitmentSupplierCostRecognition;
use App\Support\RecruitmentSupplierSettings;
use App\Support\ShelterAccountingSettings;
use Illuminate\Validation\Rule;

class ConfirmSettingRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            default => $this->view()
        };

    }

    protected function store(): array
    {
        return [
            'title' => 'nullable',
            'value' => 'nullable',
            'status' => 'nullable',
            'tax_rate_revenue' => 'nullable',
            'tax_rate_expenditure' => 'nullable',
            'data' => 'nullable|array',
            'data.invoice_supplier_workflow.title' => 'sometimes|in:invoice_supplier_workflow',
            'data.invoice_supplier_workflow.value' => 'sometimes|required|in:legacy,updated',
            'data.musaned_commission_posting.title' => 'sometimes|in:musaned_commission_posting',
            'data.musaned_commission_posting.value' => 'sometimes|required|in:separate,with_receipt',
        ] + $this->recruitmentSupplierCycleRules() + $this->shelterAccountingRules();
    }

    protected function update(): array
    {
        return [
            'title' => 'nullable',
            'value' => 'nullable',
            'status' => 'nullable',
            'tax_rate_revenue' => 'nullable',
            'tax_rate_expenditure' => 'nullable',
            'data' => 'nullable|array',
            'data.invoice_supplier_workflow.title' => 'sometimes|in:invoice_supplier_workflow',
            'data.invoice_supplier_workflow.value' => 'sometimes|required|in:legacy,updated',
            'data.musaned_commission_posting.title' => 'sometimes|in:musaned_commission_posting',
            'data.musaned_commission_posting.value' => 'sometimes|required|in:separate,with_receipt',
        ] + $this->recruitmentSupplierCycleRules() + $this->shelterAccountingRules();
    }

    /**
     * قواعد قسم «قيود المشتريات والوكلاء الخارجيون».
     *
     * تُشتق الاختيارات من كائنات الإعداد نفسها حتى لا تضيف الواجهة خياراً جديداً
     * بينما يظل طلب الحفظ يرفضه بقواعد قديمة.
     */
    protected function recruitmentSupplierCycleRules(): array
    {
        $rules = [
            'data.' . RecruitmentSupplierCostRecognition::SETTING_KEY . '.title' => [
                'sometimes',
                Rule::in([RecruitmentSupplierCostRecognition::SETTING_KEY]),
            ],
            'data.' . RecruitmentSupplierCostRecognition::SETTING_KEY . '.value' => [
                'sometimes',
                'required',
                Rule::in(RecruitmentSupplierCostRecognition::modes()),
            ],
        ];

        foreach ([
            RecruitmentSupplierSettings::COST_SOURCE,
            RecruitmentSupplierSettings::COST_CENTER_SCOPE,
            RecruitmentSupplierSettings::PAYMENT_MODE,
            RecruitmentSupplierSettings::MUSANED_COLLECTION_MODE,
        ] as $key) {
            $rules["data.{$key}.title"] = ['sometimes', Rule::in([$key])];
            $rules["data.{$key}.value"] = ['sometimes', 'required', Rule::in(RecruitmentSupplierSettings::choices($key))];
        }

        $toggle = RecruitmentSupplierSettings::SUPPLIER_LINE_COST_CENTER;
        $rules["data.{$toggle}.title"] = ['sometimes', Rule::in([$toggle])];
        $rules["data.{$toggle}.value"] = ['sometimes', 'required', Rule::in(['on', 'off'])];

        $rate = RecruitmentSupplierSettings::USD_RATE;
        $rules["data.{$rate}.title"] = ['sometimes', Rule::in([$rate])];
        $rules["data.{$rate}.value"] = ['sometimes', 'nullable', 'numeric', 'gt:0'];

        return $rules;
    }

    /** أسماء عربية واضحة بدلاً من إظهار مسار الحقل التقني في رسالة الخطأ. */
    public function attributes(): array
    {
        return [
            'data.' . RecruitmentSupplierCostRecognition::SETTING_KEY . '.value' => 'وقت إثبات تكلفة الوكيل الخارجي',
            'data.' . RecruitmentSupplierSettings::COST_SOURCE . '.value' => 'مصدر مبلغ تكلفة الوكيل الخارجي',
            'data.' . RecruitmentSupplierSettings::USD_RATE . '.value' => 'سعر صرف الدولار',
            'data.' . RecruitmentSupplierSettings::COST_CENTER_SCOPE . '.value' => 'مستوى شجرة مراكز التكلفة',
            'data.' . RecruitmentSupplierSettings::SUPPLIER_LINE_COST_CENTER . '.value' => 'تفصيل كشف حساب الوكيل بالعاملة',
            'data.' . RecruitmentSupplierSettings::PAYMENT_MODE . '.value' => 'طريقة سداد دفعات الوكيل الخارجي',
            'data.' . RecruitmentSupplierSettings::MUSANED_COLLECTION_MODE . '.value' => 'مسار قيد التحصيل من منصة مساند',
        ];
    }

    /**
     * قواعد قسم «حسابات الإيواء والترحيل» (App\Support\ShelterAccountingSettings):
     * مفتاحان منطقيان on/off، وخمسة حسابات طرفية اختيارية من fn_accounts.
     */
    protected function shelterAccountingRules(): array
    {
        $rules = [];

        foreach (ShelterAccountingSettings::TOGGLE_KEYS as $key) {
            $rules["data.{$key}.title"] = "sometimes|in:{$key}";
            $rules["data.{$key}.value"] = 'sometimes|in:on,off';
        }

        foreach (ShelterAccountingSettings::ACCOUNT_KEYS as $key) {
            $rules["data.{$key}.title"] = "sometimes|in:{$key}";
            $rules["data.{$key}.value"] = 'nullable|integer|exists:fn_accounts,id';
        }

        return $rules;
    }

    protected function destroy(): array
    {
        return [];
    }

    protected function view(): array
    {
        return [];
    }
}
