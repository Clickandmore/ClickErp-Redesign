<?php

namespace Nami\Finance\Models;

use Nami\Finance\Enums\CategoryTypes;

class Category extends Finance
{
    protected $table ='fn_categories';
    protected $fillable =['title','parent_id', 'code'];

    protected $casts = [
        'type'=> CategoryTypes::class
    ];
    public function parent() {
        return $this->belongsTo(Category::class,'parent_id');
    }

    public function children() {
        return $this->hasMany(Category::class,'parent_id');
    }

    public function item_main_category() {
        return $this->hasMany(Item::class, 'main_category_id');
    }


    public function item_sub_category() {
        return $this->hasMany(Item::class, 'sub_category_id');
    }

    public function productNumSetting() {
        return $this->hasOne(ProductNumSetting::class,'category_id');
    }
}
