<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up():void {
        Schema::create('hourly_product_attributes',function(Blueprint $t){$t->id();$t->foreignId('service_category_id')->constrained('hourly_service_categories')->restrictOnDelete();$t->foreignId('parent_id')->nullable()->constrained('hourly_product_attributes')->restrictOnDelete();$t->string('name_ar');$t->string('name_en')->nullable();$t->string('code',100);$t->decimal('price_modifier',12,2)->nullable();$t->boolean('is_active')->default(true);$t->unsignedInteger('sort_order')->default(0);$t->timestamps();$t->unique(['service_category_id','code']);});
        Schema::table('hourly_packages',fn(Blueprint $t)=>$t->foreignId('attribute_id')->nullable()->constrained('hourly_product_attributes')->restrictOnDelete());
        Schema::create('maid_product_attribute',function(Blueprint $t){$t->unsignedBigInteger('maid_id');$t->foreignId('product_attribute_id')->constrained('hourly_product_attributes')->cascadeOnDelete();$t->primary(['maid_id','product_attribute_id']);});
        foreach(DB::table('hourly_packages')->whereNotNull('brand')->where('brand','!=','')->get() as $item){
            if(!$item->service_category_id)continue;
            $root=DB::table('hourly_product_attributes')->where('service_category_id',$item->service_category_id)->where('code','legacy_brand')->first();
            $rootId=$root?->id ?? DB::table('hourly_product_attributes')->insertGetId(['service_category_id'=>$item->service_category_id,'name_ar'=>'سمات سابقة','name_en'=>'Legacy attributes','code'=>'legacy_brand','created_at'=>now(),'updated_at'=>now()]);
            $code='legacy_'.sha1(trim($item->brand));$child=DB::table('hourly_product_attributes')->where('service_category_id',$item->service_category_id)->where('code',$code)->first();
            $id=$child?->id ?? DB::table('hourly_product_attributes')->insertGetId(['service_category_id'=>$item->service_category_id,'parent_id'=>$rootId,'name_ar'=>trim($item->brand),'name_en'=>trim($item->brand),'code'=>$code,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('hourly_packages')->where('id',$item->id)->update(['attribute_id'=>$id]);
        }
    }
    public function down():void {Schema::dropIfExists('maid_product_attribute');Schema::table('hourly_packages',function(Blueprint $t){$t->dropForeign(['attribute_id']);$t->dropColumn('attribute_id');});Schema::dropIfExists('hourly_product_attributes');}
};
