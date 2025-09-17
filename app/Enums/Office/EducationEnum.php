<?php

namespace App\Enums\Office;

enum EducationEnum: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function lang()
    {
        return match ($this) {
            self::Low => __('ingaz.low'),
            self::Medium => __('ingaz.medium'),
            self::High => __('ingaz.high'),
        };
    }
}
