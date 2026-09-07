<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Services\TaxAccountResolver;

class TaxSeetingRequest extends MainRequest
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
            'name' => 'required|string|max:500',
            'account_id' => ['required', 'integer', fn ($attribute, $value, $fail) => $this->validateAccount($value, $fail)],
            'value' => ['required', 'integer', 'min:0', 'max:100', fn ($attribute, $value, $fail) => $this->validateRate($value, $fail)],
        ];
    }

    protected function update(): array
    {
        return $this->store();
    }

    protected function destroy(): array
    {
        return [];
    }

    protected function view(): array
    {
        return [];
    }

    /** حساب الضريبة يجب أن يكون طرفياً، وأن يوافق جانب الاستخدام إن كان هذا التعريف هو الافتراضي. */
    private function validateAccount($value, callable $fail): void
    {
        $account = Account::whereKey($value)->whereDoesntHave('children')->first();

        if (!$account) {
            $fail('اختر حساباً طرفياً من الدليل المحاسبي.');

            return;
        }

        $status = is_string($account->status) ? $account->status : $account->status->value;

        foreach ($this->defaultUsages() as $accountSetting) {
            if ($status !== (new TaxAccountResolver)->expectedStatus($accountSetting)) {
                $fail('طبيعة الحساب لا تتوافق مع استخدام الضريبة في الإعدادات المالية.');

                return;
            }
        }
    }

    /**
     * نسبة صفر مسموحة لتعريف عادي (معفى)، لكنها تُعطّل إقرار الضريبة بصمت
     * إذا كان هذا التعريف هو الافتراضي في الإعدادات المالية.
     */
    private function validateRate($value, callable $fail): void
    {
        if ((int) $value === 0 && $this->defaultUsages() !== []) {
            $fail('لا يمكن ضبط نسبة صفر لضريبة معتمدة كافتراضي في الإعدادات المالية.');
        }
    }

    /** إعدادات الحساب التي يُستخدم فيها هذا التعريف كافتراضي. */
    private function defaultUsages(): array
    {
        $taxId = $this->route('tax_setting');

        if (!$taxId) {
            return [];
        }

        $taxId = is_object($taxId) ? $taxId->getKey() : $taxId;
        $usages = [];

        foreach (TaxAccountResolver::RATE_SETTINGS as $accountSetting => $rateTitle) {
            $selectedId = ConfirmSetting::where('title', $rateTitle)
                ->latest('updated_at')->latest('id')->value('value');

            if ($selectedId && (int) $selectedId === (int) $taxId) {
                $usages[] = $accountSetting;
            }
        }

        return $usages;
    }
}
