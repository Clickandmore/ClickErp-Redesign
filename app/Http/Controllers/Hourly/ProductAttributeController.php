<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyProductAttribute,HourlyServiceCategory};
use App\Services\Hourly\ServiceCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule,ValidationException};
class ProductAttributeController extends Controller {
    public function store(Request $request,HourlyServiceCategory $category){return $this->save($request,$category,new HourlyProductAttribute);}
    public function update(Request $request,HourlyServiceCategory $category,HourlyProductAttribute $attribute){abort_unless($attribute->service_category_id==$category->id,404);return $this->save($request,$category,$attribute);}
    private function save(Request $request,HourlyServiceCategory $category,HourlyProductAttribute $attribute){
        $data=$request->validate(['name_ar'=>['required','string','max:255'],'name_en'=>['nullable','string','max:255'],'code'=>['required','alpha_dash','max:100',Rule::unique('hourly_product_attributes')->where('service_category_id',$category->id)->ignore($attribute->id)],'parent_id'=>['nullable','integer',Rule::exists('hourly_product_attributes','id')->where('service_category_id',$category->id)->whereNull('parent_id')],'price_modifier'=>['nullable','numeric','between:-100000,100000'],'is_active'=>['required','boolean'],'sort_order'=>['required','integer','between:0,10000']]);
        if($attribute->exists && (int)($data['parent_id']??0)!==(int)$attribute->parent_id)throw ValidationException::withMessages(['parent_id'=>'لا يمكن نقل السمة بين المستويات بعد إنشائها.']);
        if(empty($data['parent_id']))$data['price_modifier']=null;
        DB::transaction(function()use($data,$attribute,$category){$before=$attribute->exists?$attribute->toArray():null;$attribute->fill($data+['service_category_id'=>$category->id])->save();app(ServiceCategoryService::class)->audit($category,'attributes',$before?'edit':'create',$before,$attribute->toArray());});
        return redirect(route('admin.hourly-service-categories.show',$category).'#attributes')->with('success','تم حفظ السمة.');
    }
}
