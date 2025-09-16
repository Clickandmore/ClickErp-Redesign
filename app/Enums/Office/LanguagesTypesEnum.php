<?php

namespace App\Enums\Office;

enum LanguagesTypesEnum: string
{
    case Arabic = 'arabic';
    case English = 'english';
    case Amharic = 'amharic';
    case Mother = 'mother';


    public function lang()
    {
        return match ($this) {
            self::Arabic => __('ingaz.arabic'),
            self::English => __('ingaz.english'),
            self::Amharic => __('ingaz.amharic'),
            self::Mother => __('ingaz.mother'),
        };
    }

    public static function statusLang()
    {
        return [
            self::Arabic,
            self::English,
            self::Amharic,
        ];
    }
}
