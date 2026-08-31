<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
class AddIsFeaturedToOffersTable extends Migration
{
    public function up()
    {
        Schema::table('offers', function (Blueprint $table) {
            // "العرض الأفضل" — the single offer highlighted in the middle of the
            // website's exclusive-offers section.
            $table->boolean('is_featured')->default(false)->index()->after('is_active');
        });
    }

    public function down()
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
}
