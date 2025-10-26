<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('sponsorship_transfers', 'signature')) {
                $table->string('signature')->nullable()->after('status')->comment('مسار ملف التوقيع');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
