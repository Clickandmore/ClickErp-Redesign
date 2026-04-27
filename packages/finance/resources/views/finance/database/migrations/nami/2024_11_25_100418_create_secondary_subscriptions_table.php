<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('secondary_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->double('cost')->default(0);
            $table->timestamps();
        });

        DB::table('secondary_subscriptions')->insert([
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addYears(2)->toDateString(), // تعديل التاريخ ليكون بعد عامين
            'cost' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('secondary_subscriptions');
    }
};
