<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaultRecruitmentTypeId = DB::table('job_types')->where('id', 1)->value('id')
            ?? DB::table('job_types')->orderBy('id')->value('id');
        $rentalTypeId = DB::table('job_types')->where('id', 2)->value('id');
        $instantTypeId = DB::table('job_types')->where('id', 3)->value('id');

        if (!$defaultRecruitmentTypeId) {
            return;
        }

        $maidsWithoutJobTypes = DB::table('maids')
            ->leftJoin('maid_jobs', 'maids.id', '=', 'maid_jobs.maid_id')
            ->whereNull('maid_jobs.maid_id')
            ->select([
                'maids.id',
                'maids.status',
                'maids.maid_accommodation_type',
            ])
            ->get();

        foreach ($maidsWithoutJobTypes as $maid) {
            $jobTypeId = $defaultRecruitmentTypeId;

            if ((int) $maid->maid_accommodation_type === 2 && $instantTypeId) {
                $jobTypeId = $instantTypeId;
            } elseif ($maid->status === 'completed' && $rentalTypeId) {
                $jobTypeId = $rentalTypeId;
            }

            DB::table('maid_jobs')->insert([
                'maid_id' => $maid->id,
                'job_type_id' => $jobTypeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty to avoid removing legitimate maid/job-type links.
    }
};
