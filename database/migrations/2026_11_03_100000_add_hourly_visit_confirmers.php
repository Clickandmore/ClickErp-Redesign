<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** من يؤكد وصول الطاقم ومن يؤكد عودته للإيواء في كل زيارة (VisitConfirmation). */
return new class extends Migration {
    public function up(): void {
        Schema::table('hourly_visits',function(Blueprint $t){
            if(!Schema::hasColumn('hourly_visits','arrival_confirmer'))$t->string('arrival_confirmer',12)->default('auto');
            if(!Schema::hasColumn('hourly_visits','return_confirmer'))$t->string('return_confirmer',12)->default('auto');
        });
    }
    public function down(): void {
        Schema::table('hourly_visits',fn(Blueprint $t)=>$t->dropColumn(['arrival_confirmer','return_confirmer']));
    }
};
