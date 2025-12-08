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
        // إضافة أنواع الوظائف للعاملات الموجودة بناءً على حالتها
        // $maids = DB::table('maids')
        //     ->whereIn('status', ['available', 'completed', 'in_accommodation'])
        //     ->where('is_show', 1)
        //     ->get();
        $maids = DB::table('maids')
            ->whereIn('status', ['available', 'completed'])
            ->where('maid_accommodation_type','=',2)
            ->where('is_show', 1)
            ->get();

        foreach ($maids as $maid) {
            // تحديد نوع الوظيفة بناءً على حالة العاملات
            $jobTypeId = null;

            // if ($maid->status === 'in_accommodation') {
            if ($maid->maid_accommodation_type === 2) {
                // العاملات في الايواء تكون متاحة للاستلام الفوري
                $jobTypeId = 3; // إستلام فورى
            } elseif ($maid->status === 'available' && $maid->available == 1) {
                // العاملات المتاحة تكون للاستقدام
                $jobTypeId = 1; // استقدام
            } elseif ($maid->status === 'completed') {
                // العاملات المكتملة تكون للايجار
                $jobTypeId = 2; // ايجار عمالة
            }

            if ($jobTypeId) {
                // التحقق من عدم وجود العلاقة مسبقاً
                $exists = DB::table('maid_jobs')
                    ->where('maid_id', $maid->id)
                    ->where('job_type_id', $jobTypeId)
                    ->exists();

                if (!$exists) {
                    DB::table('maid_jobs')->insert([
                        'maid_id' => $maid->id,
                        'job_type_id' => $jobTypeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف جميع العلاقات المضافة
        DB::table('maid_jobs')->truncate();
    }
};
