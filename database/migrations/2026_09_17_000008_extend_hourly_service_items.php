<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('hourly_packages',function(Blueprint $t){
            $t->string('package_type')->default('one_off');$t->string('brand')->nullable();
            $t->decimal('total_price',12,2)->nullable();
            $t->unsignedInteger('duration_minutes')->nullable();
            $t->unsignedInteger('break_minutes')->default(0);$t->unsignedInteger('buffer_minutes')->default(0);
            $t->unsignedInteger('recurring_months')->default(0);
            $t->decimal('deposit_amount',12,2)->nullable();$t->decimal('monthly_amount',12,2)->nullable();
            $t->json('start_times')->nullable();
            $t->boolean('is_private')->default(false);$t->boolean('materials_included')->default(false);
            $t->boolean('for_home')->default(true);$t->boolean('for_company')->default(false);
            $t->boolean('show_on_app')->default(true);$t->boolean('ignore_closed_days')->default(false);
            $t->unsignedInteger('revision')->default(1);
        });
        Schema::create('hourly_package_logs',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('hourly_package_id')->index();$t->string('item_name');
            $t->string('type',30);$t->json('before_data')->nullable();$t->json('after_data')->nullable();
            $t->unsignedBigInteger('admin_id')->nullable();$t->string('admin_name')->nullable();$t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('hourly_package_logs');
        Schema::table('hourly_packages',fn(Blueprint $t)=>$t->dropColumn(['total_price','package_type','brand','duration_minutes','break_minutes','buffer_minutes','recurring_months','deposit_amount','monthly_amount','start_times','is_private','materials_included','for_home','for_company','show_on_app','ignore_closed_days','revision']));
    }
};
