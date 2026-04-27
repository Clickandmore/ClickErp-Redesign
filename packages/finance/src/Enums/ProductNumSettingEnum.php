<?php

namespace Nami\Finance\Enums;

enum ProductNumSettingEnum: string
{
    case AllProducts = "allProducts";
    case MainCategories = "mainCategories";
    case SubCategories = "subCategories";


    public function lang()
    {
        return match ($this) {
            ProductNumSettingEnum::AllProducts => __('finance::finance.allProducts'),
            ProductNumSettingEnum::MainCategories => __('finance::finance.MainCategories'),
            ProductNumSettingEnum::SubCategories => __('finance::finance.SubCategories'),
        };

    }
}
