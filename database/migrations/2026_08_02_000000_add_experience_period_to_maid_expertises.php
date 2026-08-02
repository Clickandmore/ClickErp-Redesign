<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maid_expertises', function (Blueprint $table) {
            // بداية ونهاية الخبرة (شهر/سنة، يُخزّن أول يوم في الشهر)
            $table->date('start_date')->nullable()->after('work_city');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('maid_expertises', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
