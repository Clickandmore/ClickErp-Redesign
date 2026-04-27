<?php

namespace Nami\Finance\Models;

use Nami\Finance\Enums\ProductNumSettingEnum;

class ProductNumSetting extends Finance
{
    protected $table ='fn_product_num_settings';
    protected $fillable =['type','code_string','code','category_id'];

    protected $casts = [
        'status'=> ProductNumSettingEnum::class,
    ];

    public function category() {
        return $this->belongsTo(Category::class,'category_id');
    }
}
