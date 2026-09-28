<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
class HourlyProductAttribute extends Model {
    protected $guarded=[];
    protected $casts=['is_active'=>'boolean','price_modifier'=>'decimal:2'];
    public function parent(){return $this->belongsTo(self::class,'parent_id');}
    public function children(){return $this->hasMany(self::class,'parent_id')->orderBy('sort_order')->orderBy('id');}
    public function category(){return $this->belongsTo(HourlyServiceCategory::class,'service_category_id');}
    public function getNameAttribute(){return app()->getLocale()==='en'?($this->name_en ?: $this->name_ar):$this->name_ar;}
    public function scopeSelectable($q){return $q->where('is_active',true)->whereNotNull('parent_id')->whereHas('parent',fn($p)=>$p->where('is_active',true));}
}
