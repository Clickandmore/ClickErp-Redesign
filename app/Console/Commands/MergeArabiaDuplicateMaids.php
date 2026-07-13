<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidExternalReference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeArabiaDuplicateMaids extends Command
{
    protected $signature = 'maids:merge-arabia-duplicates {--apply : Permanently merge and delete duplicate rows}';
    protected $description = 'Permanently merge duplicate active maids by passport into the primary Arabia record';

    public function handle(): int
    {
        $passports = $this->duplicatePassports();
        $this->info("Eligible duplicate passport groups: {$passports->count()}");

        if (!$this->option('apply')) {
            $this->warn('Dry run completed; no records changed.');
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($passports as $passport) {
            DB::transaction(function () use ($passport, &$deleted) {
                $maids = Maid::query()
                    ->whereRaw('UPPER(TRIM(passport_num)) = ?', [$passport])
                    ->orderByDesc('arabia_worker_number')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                $target = $maids->first(fn (Maid $maid) => $maid->arabia_worker_number !== null);
                if (!$target || $maids->count() < 2) {
                    return;
                }

                foreach ($maids as $maid) {
                    if ($maid->arabia_worker_number) {
                        MaidExternalReference::updateOrCreate(
                            ['source' => 'arabia', 'external_id' => (string) $maid->arabia_worker_number],
                            ['maid_id' => $target->id]
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

                    if (Schema::hasTable('activity_log')) {
                        $activityCount = DB::table('activity_log')
                            ->where('subject_id', $duplicate->id)
                            ->whereIn('subject_type', [Maid::class, 'App\\Models\\Maid'])
                            ->count();
                        DB::table('activity_log')
                            ->where('subject_id', $duplicate->id)
                            ->whereIn('subject_type', [Maid::class, 'App\\Models\\Maid'])
                            ->update(['subject_id' => $target->id]);
                        if ($activityCount) {
                            $moved['activity_log'] = $activityCount;
                        }
                    }

                    foreach (['image', 'cv_file', 'cv_preview', 'video', 'video_link'] as $field) {
                        if (!$target->{$field} && $duplicate->{$field}) {
                            $target->{$field} = $duplicate->{$field};
                        }
                    }
                    $target->save();

                    DB::table('maid_duplicate_merge_audits')->insert([
                        'passport_key' => $passport,
                        'target_maid_id' => $target->id,
                        'deleted_maid_id' => $duplicate->id,
                        'arabia_worker_number' => $duplicate->arabia_worker_number,
                        'deleted_snapshot' => json_encode($duplicate->getRawOriginal(), JSON_UNESCAPED_UNICODE),
                        'moved_relationships' => json_encode($moved, JSON_UNESCAPED_UNICODE),
                        'merged_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $duplicate->forceDelete();
                    $deleted++;
                }
            });
        }

        $this->info("Permanently deleted duplicate maid rows: {$deleted}");
        return self::SUCCESS;
    }

    private function duplicatePassports()
    {
        return Maid::query()
            ->whereNotNull('passport_num')
            ->where('passport_num', '!=', '')
            ->selectRaw('UPPER(TRIM(passport_num)) passport_key')
            ->groupBy('passport_key')
            ->havingRaw('COUNT(*) > 1')
            ->havingRaw('SUM(arabia_worker_number IS NOT NULL) > 0')
            ->pluck('passport_key');
    }

    private function mergePivot(int $source, int $target, string $table, string $related, array &$moved): void
    {
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
}
