<?php

namespace App\Enums\Office;

enum MaritalStatusEnum: string
{
    case Single = 'single';
    case Married = 'married';
    case Widow = 'widow';

    public function lang()
    {
        return match ($this) {
            self::Single => __('ingaz.single'),
            self::Married => __('ingaz.married'),
            self::Widow => __('ingaz.widow'),
        };
    }
}
