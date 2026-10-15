<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {Schema::table('hourly_packages',fn(Blueprint $t)=>$t->string('payment_policy',20)->nullable());}
    public function down(): void {Schema::table('hourly_packages',fn(Blueprint $t)=>$t->dropColumn('payment_policy'));}
};