<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};
return new class extends Migration {
    public function up(): void {
        // Preserve every legacy item, including blank names and orphaned references.
        foreach(DB::table('hourly_packages')->get(['id','category','service_category_id']) as $item){
            if($item->service_category_id && DB::table('hourly_service_categories')->where('id',$item->service_category_id)->exists())continue;
            $name=trim((string)$item->category) ?: 'خدمات ساعات سابقة';
            $id=DB::table('hourly_service_categories')->where('name_ar',$name)->value('id');
            if(!$id)$id=DB::table('hourly_service_categories')->insertGetId(['name_ar'=>$name,'slug'=>'legacy-'.\Illuminate\Support\Str::uuid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('hourly_packages')->where('id',$item->id)->update(['service_category_id'=>$id]);
        }
        Schema::table('hourly_packages',function(Blueprint $t){
            $t->json('settings_overrides')->nullable();
            $t->foreign('service_category_id','hourly_items_category_fk')->references('id')->on('hourly_service_categories')->restrictOnDelete();
        });
        Schema::create('hourly_visit_workers',function(Blueprint $t){
            $t->id();$t->foreignId('hourly_visit_id')->constrained('hourly_visits')->cascadeOnDelete();
            $t->unsignedBigInteger('maid_id')->index();$t->timestamps();$t->unique(['hourly_visit_id','maid_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('hourly_visit_workers');
        Schema::table('hourly_packages',function(Blueprint $t){$t->dropForeign('hourly_items_category_fk');$t->dropColumn('settings_overrides');});
    }
};
