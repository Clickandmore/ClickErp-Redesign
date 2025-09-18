<?php

namespace App\Enums\Office;

enum recruitmentOrderEnum: string
{
    case ChooseMaid = 'choose_maid';

    public function lang()
    {
        return match ($this) {
        };
    }
}
