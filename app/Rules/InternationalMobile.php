<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * رقم جوال دولي بصيغة +[رمز الدولة][الرقم] يطابق قواعد الترقيم لدولته
 * (الطول والبادئات حسب libphonenumber).
 */
class InternationalMobile implements Rule
{
    private string $message = '';

    public function __construct(private string $label = 'رقم الجوال')
    {
    }

    public function passes($attribute, $value): bool
    {
        $value = trim((string) $value);

        if (!preg_match('/^\+[1-9]\d{6,14}$/', $value)) {
            $this->message = "{$this->label} يجب أن يبدأ برمز الدولة (مثل +966) ويتكون من أرقام فقط";

            return false;
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $number = $util->parse($value, null);
        } catch (NumberParseException $exception) {
            $this->message = "رمز الدولة في {$this->label} غير صحيح";

            return false;
        }

        $region = $util->getRegionCodeForNumber($number) ?: '';

        if (!$util->isValidNumber($number)) {
            $this->message = "{$this->label} غير صحيح لأرقام الدولة المختارة ({$region}): تحقق من عدد الأرقام وبدايتها";

            return false;
        }

        if (!in_array($util->getNumberType($number), [PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE], true)) {
            $this->message = "{$this->label} يجب أن يكون رقم جوال وليس رقمًا أرضيًا";

            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->message ?: "{$this->label} غير صحيح";
    }
}
