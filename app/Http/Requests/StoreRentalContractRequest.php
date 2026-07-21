<?php

namespace App\Http\Requests;

use App\Models\RentalContract;
use App\Models\Country;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\Package;
use App\Models\ingaz\JobType;
use App\Support\MoyasarSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRentalContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return $this->updateRules();
        }

        return $this->storeRules();
    }

    protected function storeRules(): array
    {
        return [
            // معلومات العميل الأساسية
            'new_client_name' => 'required|string|max:255',
            'new_client_phone' => [
                'required', 'string', 'max:20',
                $this->input('new_existing_client_id')
                    ? Rule::unique('users', 'phone')->ignore((int) $this->input('new_existing_client_id'))
                    : Rule::unique('users', 'phone'),
            ],
            'account_client_type' => 'nullable|in:person,company',
            'client_nationality_id' => 'required|exists:countries,id',
            'new_client_email' => 'nullable|email|max:255',
            'new_client_national_num' => 'required|string|max:50',
            'new_client_license_number' => 'nullable|string|max:100',
            'new_client_birthdate' => 'nullable|date',
            'new_client_address' => 'nullable|string|max:500',
            'new_client_city' => [
                'required',
                'string',
                'max:100',
                Rule::exists('cities', 'name_ar')->where(function ($query) {
                    $query->where('is_active', true)
                        ->whereIn('country_id', Country::query()->select('id')->where('code', 'SA'));
                }),
            ],
            'new_client_phone2' => 'nullable|string|max:20',
            'new_client_beds_num' => 'nullable|string|max:10',
            'new_client_family_num' => 'nullable|integer|min:1',
            'new_client_children' => 'nullable|integer|min:0',
            'new_client_job' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',

            // معلومات العميل الخاصة بالتأجير
            'monthly_income' => 'nullable|numeric|min:0',

            // معلومات العاملة
            'maid_id' => 'required|exists:maids,id',

            // معلومات المسوق
            'marketer_id' => 'required|exists:admins,id',

            // معلومات العقد
            'contract_date' => 'required|date',
            'working_hours' => 'nullable|integer|min:1|max:24',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'required|numeric|gt:0',
            'sponsorship_transfer' => 'nullable|string|in:yes,no',
            'package_id' => 'required|exists:packages,id',
            'selected_package_id' => 'nullable|exists:packages,id',
            'payment_type' => 'required|string|in:cash,bank_transfer,mada,tabby,tamara,moyasar,pay_later,other',
            'payment_status' => 'required|string|in:paid,partial,pending,later,bank_pending',
            'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'paid_amount' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric|min:0',
            'monthly_cost' => 'required|numeric|min:0',
            'contract_duration_months' => 'required|integer|min:1',
            'contract_duration_days' => 'nullable|integer|min:1',
            'total_contract_value' => 'nullable|numeric|min:0',
            'ingaz_from' => 'required|date',
            'ingaz_to' => 'nullable|date',
            'rental_type' => 'nullable|string|in:hourly,daily,weekly,monthly,yearly',

            // معلومات إضافية
            'notes' => 'nullable|string|max:1000',

            // معلومات الطلب (إذا كان موجود)
            'order_id' => 'nullable|exists:orders,id',
            'new_existing_client_id' => 'nullable|exists:users,id',
        ];
    }

    protected function updateRules(): array
    {
        return [
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_nationality_id' => 'required|exists:countries,id',
            'client_email' => 'nullable|email|max:255',
            'client_national_num' => 'required|string|max:20',
            'client_birthdate' => 'nullable|date',
            'client_address' => 'nullable|string|max:500',
            'client_city' => 'required|string|max:100',
            'family_num' => 'nullable|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'profession' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'maid_id' => 'required|exists:maids,id',
            'marketer_id' => 'required|exists:admins,id',
            'contract_date' => 'required|date',
            'working_hours_per_day' => 'nullable|integer|min:1|max:24',
            'hourly_rate' => 'nullable|numeric|min:0',
            'monthly_salary' => 'nullable|numeric|min:0',
            'sponsorship_transfer_amount' => 'nullable|numeric|min:0',
            'salary_after_sponsorship' => 'nullable|numeric|min:0',
            'package_id' => 'required|exists:packages,id',
            'selected_package_id' => 'nullable|exists:packages,id',
            'payment_type' => 'required|string|in:cash,bank_transfer,mada,tabby,tamara,moyasar,pay_later,other',
            'payment_status' => 'required|string|in:paid,partial,pending,later,bank_pending',
            'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'paid_amount' => 'nullable|numeric|min:0',
            'remaining_amount' => 'nullable|numeric|min:0',
            'monthly_cost' => 'required|numeric|min:0',
            'contract_duration_months' => 'required|integer|min:1',
            'contract_duration_days' => 'nullable|integer|min:1',
            'ingaz_from' => 'required|date',
            'rental_type' => 'nullable|string|in:hourly,daily,weekly,monthly,yearly',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            // رسائل معلومات العميل
            'new_client_name.required' => 'اسم العميل مطلوب',
            'new_client_phone.required' => 'رقم جوال العميل مطلوب',
            'new_client_phone.unique' => 'رقم الجوال مسجل لعميل آخر في النظام. ابحث عنه في قائمة "العملاء الحاليين" واختره منها.',
            'client_nationality_id.required' => 'جنسية العميل مطلوبة',
            'client_nationality_id.exists' => 'الجنسية المختارة غير صحيحة',
            'new_client_email.email' => 'البريد الإلكتروني غير صحيح',
            'new_client_national_num.required' => 'رقم الهوية مطلوب',
            'new_client_city.required' => 'المدينة مطلوبة',
            'new_client_city.exists' => 'يرجى اختيار مدينة سعودية صحيحة من القائمة',

            // رسائل معلومات العميل الخاصة بالتأجير
            'monthly_income.numeric' => 'الدخل الشهري يجب أن يكون رقماً',
            'monthly_income.min' => 'الدخل الشهري يجب أن يكون أكبر من أو يساوي صفر',

            // رسائل معلومات العاملة
            'maid_id.required' => 'يرجى اختيار العاملة',
            'maid_id.exists' => 'العاملة المختارة غير صحيحة',

            // رسائل معلومات المسوق
            'marketer_id.required' => 'يرجى اختيار المسوق',
            'marketer_id.exists' => 'المسوق المختار غير صحيح',

            // رسائل معلومات العقد
            'contract_date.required' => 'تاريخ العقد مطلوب',
            'working_hours.integer' => 'عدد ساعات العمل يجب أن يكون رقماً صحيحاً',
            'working_hours.min' => 'عدد ساعات العمل يجب أن يكون على الأقل ساعة واحدة',
            'working_hours.max' => 'عدد ساعات العمل لا يمكن أن يتجاوز 24 ساعة',
            'hourly_rate.numeric' => 'أجرة الساعة يجب أن تكون رقماً',
            'monthly_salary.numeric' => 'الراتب الشهري يجب أن يكون رقماً',
            'monthly_salary.required' => 'يجب تحديد الراتب الشهري لأنه معفى من الضريبة',
            'monthly_salary.gt' => 'يجب أن يكون الراتب الشهري أكبر من صفر حتى لا تُحتسب الضريبة على كامل قيمة العقد',
            'package_id.required' => 'يرجى اختيار الباقة',
            'package_id.exists' => 'الباقة المختارة غير صحيحة',
            'payment_type.required' => 'يرجى اختيار طريقة الدفع',
            'payment_type.in' => 'طريقة الدفع المختارة غير صحيحة',
            'payment_status.required' => 'يرجى تحديد حالة الدفع',
            'payment_status.in' => 'حالة الدفع المختارة غير صحيحة',
            'payment_receipt.file' => 'مرفق الدفع غير صالح',
            'payment_receipt.mimes' => 'صيغة مرفق الدفع يجب أن تكون pdf أو jpg أو jpeg أو png',
            'payment_receipt.max' => 'حجم مرفق الدفع يجب ألا يتجاوز 4 ميجابايت',
            'paid_amount.numeric' => 'المبلغ المدفوع يجب أن يكون رقماً',
            'paid_amount.min' => 'المبلغ المدفوع يجب أن يكون أكبر من أو يساوي صفر',
            'monthly_cost.required' => 'التكلفة الشهرية مطلوبة',
            'monthly_cost.numeric' => 'التكلفة الشهرية يجب أن تكون رقماً',
            'contract_duration_months.required' => 'مدة العقد بالشهور مطلوبة',
            'contract_duration_months.integer' => 'مدة العقد بالشهور يجب أن تكون رقماً صحيحاً',
            'contract_duration_months.min' => 'مدة العقد يجب أن تكون شهر واحد على الأقل',
            'contract_duration_days.integer' => 'مدة العقد بالأيام يجب أن تكون رقماً صحيحاً',
            'contract_duration_days.min' => 'مدة العقد يجب أن تكون يوم واحد على الأقل',
            'ingaz_from.required' => 'تاريخ بداية الإنجاز مطلوب',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $currentContractId = $this->route('RentContract') ?? $this->route('id');
            $currentContract = $currentContractId ? RentalContract::find($currentContractId) : null;
            $maid = $this->maid_id
                ? Maid::with('nationality.translations')->find($this->maid_id)
                : null;
            $selectedOrder = $this->order_id
                ? Order::query()
                    ->whereKey($this->order_id)
                    ->where('maid_id', $this->maid_id)
                    ->whereIn('status', ['under_process', 'done'])
                    ->first()
                : null;
            $isSelectedOrderMaid = (bool) $selectedOrder;

            if ($this->maid_id) {
                if (
                    $maid
                    && !$maid->available
                    && !$isSelectedOrderMaid
                    && (!$currentContract || (int) $currentContract->maid_id !== (int) $maid->id)
                ) {
                    $validator->errors()->add('maid_id', 'العاملة المختارة غير متاحة حالياً');
                }

                if ($maid && !$maid->jobTypes()->where('job_type_id', $this->getRentalJobTypeId())->exists()) {
                    $validator->errors()->add('maid_id', 'العاملة المختارة ليست من نوع تأجير');
                }

                if ($maid) {
                    $activeRentalContractExists = $maid->rentalContracts()
                        ->whereIn('status', ['new', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'active', 'transferred', 'suspended', 'frozen'])
                        ->when($currentContract, function ($query) use ($currentContract) {
                            $query->where('id', '!=', $currentContract->id);
                        })
                        ->exists();

                    if ($activeRentalContractExists) {
                        $validator->errors()->add('maid_id', 'العاملة المختارة مرتبطة بعقد إيجار نشط حالياً');
                    }
                }
            }

            $packageId = $this->input('package_id') ?: $this->input('selected_package_id');

            if ($packageId && $this->maid_id) {
                $package = Package::with('nationality.translations')->find($packageId);

                if ($package && $maid && !$this->packageMatchesMaidNationality($package, $maid)) {
                    $validator->errors()->add('package_id', 'الباقة المختارة لا تطابق جنسية العاملة');
                }
            }

            $contractValue = (float) ($this->total_contract_value ?: (($this->monthly_cost ?? 0) * ($this->contract_duration_months ?? 0)));
            $paidAmount = (float) ($this->paid_amount ?? 0);
            $paymentType = (string) $this->payment_type;
            $paymentStatus = (string) $this->payment_status;
            $moyasarAlreadyPaid = ($currentContract?->info?->moyasar_payment_status === 'paid')
                || ($currentContract?->info?->payment_type === 'moyasar' && $currentContract?->info?->payment_status === 'paid');

            if ($paymentType === 'moyasar' && !MoyasarSettings::isEnabled() && !$moyasarAlreadyPaid) {
                $validator->errors()->add('payment_type', 'بوابة ميسر غير مفعلة حالياً من إعدادات النظام');
            }

            if ($paymentType === 'moyasar' && $paymentStatus !== 'pending' && !$moyasarAlreadyPaid) {
                $validator->errors()->add('payment_status', 'عند اختيار ميسر يجب أن تكون حالة الدفع معلقة حتى يتم تأكيد الدفع تلقائياً من ميسر');
            }

            if ($paymentType === 'pay_later' && $paymentStatus !== 'later') {
                $validator->errors()->add('payment_status', 'عند اختيار طريقة الدفع لاحقاً يجب أن تكون حالة الدفع "الدفع لاحقاً"');
            }

            if ($paymentStatus === 'later' && $paymentType !== 'pay_later') {
                $validator->errors()->add('payment_type', 'حالة الدفع لاحقاً تتطلب اختيار طريقة الدفع لاحقاً');
            }

            if ($paymentStatus === 'bank_pending' && $paymentType !== 'bank_transfer') {
                $validator->errors()->add('payment_type', 'حالة بانتظار التحويل تتطلب اختيار طريقة الدفع تحويل بنكي');
            }

            if ($this->payment_status === 'partial') {
                if ($paidAmount <= 0) {
                    $validator->errors()->add('paid_amount', 'أدخل المبلغ المدفوع عند اختيار حالة مدفوع جزئياً');
                }

                if ($contractValue > 0 && $paidAmount >= $contractValue) {
                    $validator->errors()->add('paid_amount', 'إذا تم سداد كامل المبلغ اختر حالة تم الدفع بدلاً من مدفوع جزئياً');
                }
            }

            if ($this->payment_status === 'paid' && $contractValue > 0 && $paidAmount > $contractValue) {
                $validator->errors()->add('paid_amount', 'المبلغ المدفوع لا يمكن أن يتجاوز قيمة العقد');
            }
        });
    }

    protected function packageMatchesMaidNationality(?Package $package, ?Maid $maid): bool
    {
        if (!$package || !$maid) {
            return false;
        }

        if (!$package->nationality_id) {
            return true;
        }

        if ((int) $package->nationality_id === (int) $maid->nationality_id) {
            return true;
        }

        $maidTerms = $this->buildNationalityTerms([
            $maid->nationality?->title,
            $maid->nationality?->sub_title,
        ]);

        $packageTerms = $this->buildNationalityTerms([
            $package->nationality?->title,
            $package->nationality?->sub_title,
        ]);

        if (!empty(array_intersect($maidTerms, $packageTerms))) {
            return true;
        }

        $packageTitle = $this->normalizeArabicText($package->title ?? '');

        foreach ($maidTerms as $maidTerm) {
            if ($maidTerm !== '' && Str::contains($packageTitle, $maidTerm)) {
                return true;
            }
        }

        return false;
    }

    protected function buildNationalityTerms(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => $this->normalizeArabicText($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function getRentalJobTypeId(): int
    {
        $jobType = JobType::with('translations')->get()->first(function ($type) {
            $title = $this->normalizeArabicText($type->title ?? '');

            foreach (['ايجار', 'إيجار', 'تأجير', 'rent', 'rental'] as $keyword) {
                if (Str::contains($title, $this->normalizeArabicText($keyword))) {
                    return true;
                }
            }

            return false;
        });

        return (int) ($jobType?->id ?? 2);
    }

    protected function normalizeArabicText(?string $value): string
    {
        $value = trim((string) $value);
        $value = Str::lower($value);

        return str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $value);
    }
}
