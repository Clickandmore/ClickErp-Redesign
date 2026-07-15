<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('maids', 'active_passport_key')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->string('active_passport_key', 150)
                    ->nullable()
                    ->storedAs("CASE WHEN deleted_at IS NULL AND passport_num IS NOT NULL AND TRIM(passport_num) <> '' THEN UPPER(TRIM(passport_num)) ELSE NULL END");
            });
        }

        $this->mergeDuplicateMaidsByPassport();
        $this->ensureNoActivePassportDuplicates();

        if (!$this->indexExists('maids_active_passport_key_unique')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->unique('active_passport_key', 'maids_active_passport_key_unique');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('maids_active_passport_key_unique')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropUnique('maids_active_passport_key_unique');
            });
        }

        if (Schema::hasColumn('maids', 'active_passport_key')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropColumn('active_passport_key');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        return collect(DB::select('SHOW INDEX FROM `maids` WHERE Key_name = ?', [$indexName]))->isNotEmpty();
    }

    private function mergeDuplicateMaidsByPassport(): void
    {
        foreach ($this->duplicatePassportKeys() as $passportKey) {
            DB::transaction(function () use ($passportKey) {
                $maids = DB::table('maids')
                    ->whereNull('deleted_at')
                    ->whereRaw('UPPER(TRIM(passport_num)) = ?', [$passportKey])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                if ($maids->count() < 2) {
                    return;
                }

                $target = $this->selectMergeTarget($maids);

                foreach ($maids as $maid) {
                    if ($maid->arabia_worker_number && Schema::hasTable('maid_external_references')) {
                        DB::table('maid_external_references')->updateOrInsert(
                            ['source' => 'arabia', 'external_id' => (string) $maid->arabia_worker_number],
                            ['maid_id' => $target->id, 'updated_at' => now(), 'created_at' => now()]
                        );
                    }
                }

                foreach ($maids->where('id', '!=', $target->id) as $duplicate) {
                    $moved = [];
                    $this->mergePivot($duplicate->id, $target->id, 'maid_job_titles', 'job_title_id', $moved);
                    $this->mergePivot($duplicate->id, $target->id, 'maid_jobs', 'job_type_id', $moved);
                    $this->mergeTranslations($duplicate->id, $target->id, $moved);

                    foreach ($this->directReferences() as [$table, $column]) {
                        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                            continue;
                        }

                        $count = DB::table($table)->where($column, $duplicate->id)->count();
                        if ($count > 0) {
                            DB::table($table)->where($column, $duplicate->id)->update([$column => $target->id]);
                            $moved[$table] = ($moved[$table] ?? 0) + $count;
                        }
                    }

                    $this->moveActivityLog($duplicate->id, $target->id, $moved);
                    $this->fillMissingTargetMedia($target->id, $duplicate);
                    $this->auditMerge($passportKey, $target->id, $duplicate, $moved);

                    DB::table('maids')->where('id', $duplicate->id)->delete();
                }
            });
        }
    }

    private function duplicatePassportKeys()
    {
        return DB::table('maids')
            ->whereNull('deleted_at')
            ->whereNotNull('passport_num')
            ->whereRaw("TRIM(passport_num) <> ''")
            ->selectRaw('UPPER(TRIM(passport_num)) passport_key')
            ->groupBy('passport_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('passport_key');
    }

    private function selectMergeTarget($maids): object
    {
        return $maids->sort(function ($left, $right) {
            return $this->compareMergePriority(
                $this->mergePriority($right),
                $this->mergePriority($left)
            );
        })->first();
    }

    private function mergePriority(object $maid): array
    {
        return [
            !empty($maid->arabia_worker_number) ? 1 : 0,
            (int) ($maid->arabia_worker_number ?? 0),
            $this->relationshipCount($maid->id),
            $this->filledDataScore($maid),
            strtotime((string) ($maid->updated_at ?? $maid->created_at ?? '')) ?: 0,
            (int) $maid->id,
        ];
    }

    private function compareMergePriority(array $left, array $right): int
    {
        foreach ($left as $index => $value) {
            $comparison = $value <=> $right[$index];
            if ($comparison !== 0) {
                return $comparison;
            }
        }

        return 0;
    }

    private function relationshipCount(int $maidId): int
    {
        $count = 0;

        foreach ($this->directReferences() as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $count += DB::table($table)->where($column, $maidId)->count();
            }
        }

        foreach ([['maid_job_titles', 'job_title_id'], ['maid_jobs', 'job_type_id']] as [$table]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'maid_id')) {
                $count += DB::table($table)->where('maid_id', $maidId)->count();
            }
        }

        if (Schema::hasTable('maid_translations')) {
            $count += DB::table('maid_translations')->where('maid_id', $maidId)->count();
        }

        return $count;
    }

    private function filledDataScore(object $maid): int
    {
        $score = 0;
        foreach ([
            'name', 'gender', 'nationality_id', 'religion_id', 'id_number',
            'border_number', 'passport_issue_date', 'passport_expiry_date',
            'price', 'salary', 'age', 'birth_date', 'office_id', 'status',
            'arrival_date', 'image', 'cv_file', 'cv_preview', 'video',
            'video_link',
        ] as $field) {
            if (property_exists($maid, $field) && !empty($maid->{$field})) {
                $score++;
            }
        }

        return $score;
    }

    private function ensureNoActivePassportDuplicates(): void
    {
        $duplicates = DB::table('maids')
            ->whereNull('deleted_at')
            ->whereNotNull('passport_num')
            ->whereRaw("TRIM(passport_num) <> ''")
            ->selectRaw('UPPER(TRIM(passport_num)) passport_key, GROUP_CONCAT(id ORDER BY id) maid_ids')
            ->groupBy('passport_key')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($duplicates->isEmpty()) {
            return;
        }

        $examples = $duplicates
            ->map(fn ($row) => "{$row->passport_key} (maids: {$row->maid_ids})")
            ->implode(', ');

        throw new RuntimeException(
            'Cannot add maids active passport unique index while active duplicate passports remain: ' . $examples
        );
    }

    private function mergePivot(int $source, int $target, string $table, string $related, array &$moved): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'maid_id') || !Schema::hasColumn($table, $related)) {
            return;
        }

        $rows = DB::table($table)->where('maid_id', $source)->get();
        foreach ($rows as $row) {
            if (DB::table($table)->where('maid_id', $target)->where($related, $row->{$related})->exists()) {
                DB::table($table)->where('id', $row->id)->delete();
            } else {
                DB::table($table)->where('id', $row->id)->update(['maid_id' => $target]);
            }
        }

        if ($rows->isNotEmpty()) {
            $moved[$table] = $rows->count();
        }
    }

    private function mergeTranslations(int $source, int $target, array &$moved): void
    {
        if (!Schema::hasTable('maid_translations')) {
            return;
        }

        $rows = DB::table('maid_translations')->where('maid_id', $source)->get();
        foreach ($rows as $row) {
            if (DB::table('maid_translations')->where('maid_id', $target)->where('locale', $row->locale)->exists()) {
                DB::table('maid_translations')->where('id', $row->id)->delete();
            } else {
                DB::table('maid_translations')->where('id', $row->id)->update(['maid_id' => $target]);
            }
        }

        if ($rows->isNotEmpty()) {
            $moved['maid_translations'] = $rows->count();
        }
    }

    private function moveActivityLog(int $source, int $target, array &$moved): void
    {
        if (!Schema::hasTable('activity_log')) {
            return;
        }

        $count = DB::table('activity_log')
            ->where('subject_id', $source)
            ->whereIn('subject_type', ['App\\Models\\ingaz\\Maid', 'App\\Models\\Maid'])
            ->count();

        if ($count === 0) {
            return;
        }

        DB::table('activity_log')
            ->where('subject_id', $source)
            ->whereIn('subject_type', ['App\\Models\\ingaz\\Maid', 'App\\Models\\Maid'])
            ->update(['subject_id' => $target]);

        $moved['activity_log'] = $count;
    }

    private function fillMissingTargetMedia(int $targetId, object $duplicate): void
    {
        $target = DB::table('maids')->where('id', $targetId)->first();
        if (!$target) {
            return;
        }

        $updates = [];
        foreach (['image', 'cv_file', 'cv_preview', 'video', 'video_link'] as $field) {
            if (Schema::hasColumn('maids', $field) && empty($target->{$field}) && !empty($duplicate->{$field})) {
                $updates[$field] = $duplicate->{$field};
            }
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();
            DB::table('maids')->where('id', $targetId)->update($updates);
        }
    }

    private function auditMerge(string $passportKey, int $targetId, object $duplicate, array $moved): void
    {
        if (!Schema::hasTable('maid_duplicate_merge_audits')) {
            return;
        }

        DB::table('maid_duplicate_merge_audits')->insert([
            'passport_key' => $passportKey,
            'target_maid_id' => $targetId,
            'deleted_maid_id' => $duplicate->id,
            'arabia_worker_number' => $duplicate->arabia_worker_number,
            'deleted_snapshot' => json_encode((array) $duplicate, JSON_UNESCAPED_UNICODE),
            'moved_relationships' => json_encode($moved, JSON_UNESCAPED_UNICODE),
            'merged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function directReferences(): array
    {
        return [
            ['agent_maid_bulk_import_rows', 'maid_id'], ['contracts', 'maid_id'],
            ['deportation_procedures', 'maid_id'], ['financial_requests', 'maid_id'],
            ['hr_employee', 'maid_id'], ['hr_payroll_items', 'maid_id'],
            ['ingaz_delegations', 'maid_id'], ['istiqdam_contracts', 'assigned_maid_id'],
            ['maid_attachments', 'maid_id'], ['maid_bulk_import_rows', 'maid_id'],
            ['maid_expertises', 'maid_id'], ['maid_offer', 'maid_id'],
            ['maid_recruitment_procedures', 'maid_id'], ['maid_unavailable_records', 'maid_id'],
            ['orders', 'maid_id'], ['recruitment_requests', 'maid_id'],
            ['rental_contracts', 'maid_id'], ['rental_financial_adjustments', 'maid_id'],
            ['return_requests', 'maid_id'], ['shelter_living_expense_allocations', 'maid_id'],
            ['transfer_service_contracts', 'maid_id'], ['travel_histories', 'maid_id'],
            ['maid_external_references', 'maid_id'],
        ];
    }
};
