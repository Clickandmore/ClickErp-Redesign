<?php
namespace App\Services\Hourly;
use App\Models\Hourly\{HourlyProductAttribute,HourlyPackage};
use Illuminate\Support\Facades\Schema;
class ProductAttributeService {
    public function available(HourlyPackage $item):bool {
        if(!$item->attribute_id)return true;
        $a=$item->productAttribute;
        return $a && $a->service_category_id==$item->service_category_id && $a->parent_id && $a->is_active && $a->parent?->is_active;
    }
    public function modifier(HourlyPackage $item):int {return $item->attribute_id?(int)round((float)$item->productAttribute?->price_modifier*100):0;}
    public function groups(HourlyPackage $item,?int $cityId,bool $panel):array {
        if(!Schema::hasTable('hourly_product_attributes'))return [];
        $rules=app(HourlyBookingRules::class);
        $items=HourlyPackage::where('service_category_id',$item->service_category_id)->where('is_active',true)
            ->where('package_tier_id',$item->package_tier_id)->where('hours_per_visit',$item->hours_per_visit)->where('visits_count',$item->visits_count)->where('workers_per_visit',$item->workers_per_visit)
            ->when($cityId,fn($q)=>$q->forCity($cityId))->when(!$panel,fn($q)=>$q->where('show_on_app',true)->where('is_private',false))->with('productAttribute.parent')->get()
            ->filter(fn($p)=>$this->available($p)&&($panel||$rules->visibleToCustomers($p)));
        return HourlyProductAttribute::where('service_category_id',$item->service_category_id)->whereNull('parent_id')->where('is_active',true)->with('children')->orderBy('sort_order')->get()->map(function($root)use($items,$rules,$panel){
            return ['id'=>$root->id,'name'=>$root->name,'options'=>$root->children->where('is_active',true)->map(fn($a)=>['id'=>$a->id,'name'=>$a->name,'items'=>$items->where('attribute_id',$a->id)->map(fn($p)=>['id'=>$p->id,'name'=>$p->title,'total'=>$rules->quote($p,max($p->workers_per_visit,$rules->settings()->steppers($p,$panel?'panel':'web_app')['workers']['min']??1),$rules->settings()->steppers($p,$panel?'panel':'web_app')['quantity']['min']??1,$panel)['total']/100])->values()->all()])->filter(fn($a)=>count($a['items']))->values()->all()];
        })->filter(fn($g)=>count($g['options']))->values()->all();
    }
}
