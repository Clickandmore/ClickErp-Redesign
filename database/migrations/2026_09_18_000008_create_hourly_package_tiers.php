<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up():void {
        Schema::create('hourly_package_tiers',function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('name_ar');$t->string('name_en')->nullable();$t->string('frequency');$t->decimal('visits_per_week',5,2);$t->boolean('is_active')->default(true);$t->unsignedInteger('sort_order')->default(0);$t->timestamps();});
        (new \Database\Seeders\HourlyPackageTiersSeeder)->run();
        Schema::table('hourly_packages',function(Blueprint $t){$t->foreignId('package_tier_id')->nullable()->constrained('hourly_package_tiers')->restrictOnDelete();});
        foreach(DB::table('hourly_packages')->get() as $item){
            $old=trim((string)$item->package_type);$code=strtolower(str_replace(['-',' '],'_',$old));
            if(preg_match('/^(?:weekly_([1-7])|([1-7])_times_a_week)$/',$code,$m))$code='weekly_'.($m[1] ?: $m[2]);
            if(in_array($code,['','one_off','once','oneoff']) && $item->visits_count==1 && !$item->recurring_months)$code='one_off';
            elseif(in_array($code,['','one_off','once','oneoff']))$code='legacy_'.$item->id;
            $tier=DB::table('hourly_package_tiers')->where('code',$code)->first();
            if(!$tier){$id=DB::table('hourly_package_tiers')->insertGetId(['code'=>'legacy_'.$item->id,'name_ar'=>$old ?: 'باقة سابقة '.$item->id,'name_en'=>$old ?: 'Legacy '.$item->id,'frequency'=>$item->visits_count>1?'weekly':'one_off','visits_per_week'=>$item->visits_count/max(1,$item->recurring_months)/4,'is_active'=>true,'sort_order'=>100,'created_at'=>now(),'updated_at'=>now()]);}
            else $id=$tier->id;
            DB::table('hourly_packages')->where('id',$item->id)->update(['package_tier_id'=>$id]);
        }
    }
    public function down():void {Schema::table('hourly_packages',function(Blueprint $t){$t->dropForeign(['package_tier_id']);$t->dropColumn('package_tier_id');});Schema::dropIfExists('hourly_package_tiers');}
};
