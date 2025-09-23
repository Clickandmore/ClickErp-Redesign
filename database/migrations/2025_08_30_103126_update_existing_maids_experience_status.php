<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث العاملات التي لديها سنوات خبرة
        DB::table('maids')
            ->whereNotNull('experience_years')
            ->where('experience_years', '!=', '')
            ->where('experience_years', '!=', '0')
            ->update(['experience_status' => 'has_experience']);

        // تحديث العاملات التي ليس لديها سنوات خبرة
        DB::table('maids')
            ->where(function($query) {
                $query->whereNull('experience_years')
                      ->orWhere('experience_years', '=', '')
                      ->orWhere('experience_years', '=', '0');
            })
            ->update(['experience_status' => 'no_experience']);

        echo "تم تحديث حالة الخبرة للعاملات بنجاح!\n";
        echo "العاملات التي سبق لها العمل: " . DB::table('maids')->where('experience_status', 'has_experience')->count() . "\n";
        echo "العاملات التي لم يسبق لها العمل: " . DB::table('maids')->where('experience_status', 'no_experience')->count() . "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يمكن التراجع عن هذا التحديث لأنه يعتمد على البيانات الموجودة
        echo "لا يمكن التراجع عن هذا التحديث لأنه يعتمد على البيانات الموجودة.\n";
    }
};
