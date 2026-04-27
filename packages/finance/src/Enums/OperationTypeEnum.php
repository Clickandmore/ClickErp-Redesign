<?php

namespace Nami\Finance\Enums;

enum OperationTypeEnum: int
{
    case Basics = 1;
    case Special = 2;
    case General = 3;

    public function lang()
    {
        return match ($this) {
            OperationTypeEnum::Basics => 'Basics',
            OperationTypeEnum::Special => 'Special',
            OperationTypeEnum::General => 'General',
        };
    }
}
