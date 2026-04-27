<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fn_clients', function (Blueprint $table) {
            // فيلدات مطلوبة لهئة الزكاة
            $table->boolean('is_company')->after('email')->default(0)->comment('شركة ولا عميل عادي');
            $table->string('postal_zone')->nullable()->default(' ')->after('address');
            $table->string('city')->nullable()->default(' ')->after('address');
            $table->string('tax_number')->nullable()->default(' ')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_clients', function (Blueprint $table) {
            //
        });
    }
};
