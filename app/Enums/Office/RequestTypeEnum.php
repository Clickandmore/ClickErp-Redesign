<?php

namespace App\Enums\Office;

enum RequestTypeEnum: string
{
    case Admin = 'admin';
    case Employee = 'employee';

    public function lang()
    {
        return match ($this) {
            self::Admin => __('ingaz.admin'),
            self::Employee => __('ingaz.employee'),
        };
    }
}
