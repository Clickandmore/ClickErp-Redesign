<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fn_purchase_items', function (Blueprint $table) {
            $table->double('return_amount')->default(0)->after('amount');  // إضافة عمود return_amount بعد amount
        });
    }

    public function down()
    {
        Schema::table('fn_purchase_items', function (Blueprint $table) {
            $table->dropColumn('return_amount');  // حذف العمود في حالة التراجع
        });
    }
};
