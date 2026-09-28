@php($attributeRoots=\Illuminate\Support\Facades\Schema::hasTable('hourly_product_attributes') ? \App\Models\Hourly\HourlyProductAttribute::where('service_category_id',$category->id)->whereNull('parent_id')->with('children')->orderBy('sort_order')->get() : collect())
<div class="ht-card"><div class="ht-head"><h5>{{ __('hourly.product_attributes') }}</h5></div><div class="ht-body">
@foreach($attributeRoots as $root)<div class="border rounded p-3 mb-3"><h6>{{ $root->name }}</h6>@foreach(collect([$root])->concat($root->children) as $attribute)<div class="{{ $attribute->parent_id ? 'ms-4 border-start ps-3' : '' }}">@include('admin.hourly_service_categories._attribute-form')</div>@endforeach</div>@endforeach
@if($canEdit)<h6>{{ __('hourly.attribute_add') }}</h6>@include('admin.hourly_service_categories._attribute-form',['attribute'=>new \App\Models\Hourly\HourlyProductAttribute(['is_active'=>true,'sort_order'=>0])])@endif
</div></div>
