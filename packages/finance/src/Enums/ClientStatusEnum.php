<?php

namespace Nami\Finance\Enums;

enum ClientStatusEnum: int
{
    case Active = 1;
    case InActive = 0;


    public function lang(): string
    {
        return match ($this) {
            self::Active => __('finance::finance.active'),
            self::InActive => __('finance::finance.disactive'),
        };
    }
    public function class(): string
    {
        return match ($this) {
            self::Active => 'btn-success',
            self::InActive => 'btn-danger',
        };
    }

    public function updateStatusButtonClass(): string
    {
        return match ($this) {
            self::Active => 'update-status',
            self::InActive => 'update-status',
        };
    }

}
