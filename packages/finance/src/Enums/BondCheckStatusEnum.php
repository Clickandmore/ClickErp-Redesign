<?php

namespace Nami\Finance\Enums;

enum BondCheckStatusEnum: int
{
    case NewSarf = 0; // جديد صرف
    case NewQabd = 1; // جديد قبض
    case CollectSarf = 2; // تحصيل صرف
    case RefuseSarf = 3; // رفض صرف
    case CollectQabd = 4; // تحصيل قبض
    case TazherQabd = 5; // تظهير قبض

    case RefuseQabd = 6; // رفض قبض

    public function lang()
    {
        return match ($this) {
            BondCheckStatusEnum::NewSarf => __("finance::finance.NewSarf"),
            BondCheckStatusEnum::NewQabd => __("finance::finance.NewQabd"),
            BondCheckStatusEnum::CollectSarf => __("finance::finance.CollectSarf"),
            BondCheckStatusEnum::RefuseSarf => __("finance::finance.RefuseSarf"),
            BondCheckStatusEnum::CollectQabd => __("finance::finance.CollectQabd"),
            BondCheckStatusEnum::TazherQabd => __("finance::finance.TazherQabd"),
            BondCheckStatusEnum::RefuseQabd => __("finance::finance.RefuseQabd"),

        };
    }
}
