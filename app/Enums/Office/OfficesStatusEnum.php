<?php

namespace App\Enums\Office;

enum OfficesStatusEnum: string
{
    case Active = 'active';
    case DisActive = 'dis_active';

    public function lang()
    {
        return match ($this) {
            self::Active => __('ingaz.active'),
            self::DisActive => __('ingaz.dis_active'),
        };
    }
}
