<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use App\Models\ingaz\MaidExternalReference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeArabiaDuplicateMaids extends Command
{
    protected $signature = 'maids:merge-arabia-duplicates {--apply : Apply the merge instead of a dry run}';
    protected $description = 'Merge duplicate active maids by passport while retaining every Arabia worker reference';

    public function handle(): int
    {
        $groups = Maid::query()
            ->whereNotNull('passport_num')
            ->where('passport_num', '!=', '')
            ->selectRaw('UPPER(TRIM(passport_num)) passport_key, COUNT(*) total')
            ->groupBy('passport_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('passport_key');

        $mergeGroups = $groups->filter(function ($passport) {
            return Maid::whereRaw('UPPER(TRIM(passport_num)) = ?', [$passport])
                ->whereNotNull('arabia_worker_number')
                ->exists();
        })->values();

        $this->info("Duplicate Arabia passport groups: {$mergeGroups->count()}");
        if (!$this->option('apply')) {
            $this->warn('Dry run only. Re-run with --apply to merge.');
            return self::SUCCESS;
        }

        $merged = 0;
        foreach ($mergeGroups as $passport) {
            DB::transaction(function () use ($passport, &$merged) {
                $maids = Maid::whereRaw('UPPER(TRIM(passport_num)) = ?', [$passport])
                    ->orderByDesc('arabia_worker_number')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                $target = $maids->first(fn (Maid $maid) => filled($maid->arabia_worker_number));
                if (!$target) {
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
                    $this->mergePivot($duplicate->id, $target->id, 'maid_job_titles', 'job_title_id');
                    $this->mergePivot($duplicate->id, $target->id, 'maid_jobs', 'job_type_id');
                    $this->mergeTranslations($duplicate->id, $target->id);

                    foreach ($this->directMaidReferences() as [$table, $column]) {
                        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                            DB::table($table)->where($column, $duplicate->id)->update([$column => $target->id]);
                        }
                    }

                    if (Schema::hasTable('activity_log')) {
                        DB::table('activity_log')
                            ->where('subject_id', $duplicate->id)
                            ->whereIn('subject_type', [Maid::class, 'App\\Models\\Maid'])
                            ->update(['subject_id' => $target->id]);
                    }

                    foreach (['image', 'cv_file', 'cv_preview'] as $mediaField) {
                        if (!$target->{$mediaField} && $duplicate->{$mediaField}) {
                            $target->{$mediaField} = $duplicate->{$mediaField};
                        }
                    }
                    $target->save();
                    $duplicate->delete();
                    $merged++;
                }
            });
        }

        $this->info("Merged duplicate records: {$merged}");
        return self::SUCCESS;
    }

    private function mergePivot(int $sourceId, int $targetId, string $table, string $relatedColumn): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $ids = DB::table($table)->where('maid_id', $sourceId)->pluck($relatedColumn);
        foreach ($ids as $id) {
            DB::table($table)->updateOrInsert(
                ['maid_id' => $targetId, $relatedColumn => $id],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
        DB::table($table)->where('maid_id', $sourceId)->delete();
    }

    private function mergeTranslations(int $sourceId, int $targetId): void
    {
        if (!Schema::hasTable('maid_translations')) {
            return;
        }
        foreach (DB::table('maid_translations')->where('maid_id', $sourceId)->get() as $translation) {
            $data = (array) $translation;
            unset($data['id'], $data['maid_id'], $data['locale'], $data['created_at']);
            $data['updated_at'] = now();
            DB::table('maid_translations')->updateOrInsert(
                ['maid_id' => $targetId, 'locale' => $translation->locale],
                $data
            );
        }
        DB::table('maid_translations')->where('maid_id', $sourceId)->delete();
    }

    private function directMaidReferences(): array
    {
        return [
            ['agent_maid_bulk_import_rows', 'maid_id'],
            ['contracts', 'maid_id'],
            ['deportation_procedures', 'maid_id'],
            ['financial_requests', 'maid_id'],
            ['hr_employee', 'maid_id'],
            ['hr_payroll_items', 'maid_id'],
            ['ingaz_delegations', 'maid_id'],
            ['istiqdam_contracts', 'assigned_maid_id'],
            ['maid_attachments', 'maid_id'],
            ['maid_bulk_import_rows', 'maid_id'],
            ['maid_expertises', 'maid_id'],
            ['maid_offer', 'maid_id'],
            ['maid_recruitment_procedures', 'maid_id'],
            ['maid_unavailable_records', 'maid_id'],
            ['orders', 'maid_id'],
            ['recruitment_requests', 'maid_id'],
            ['rental_contracts', 'maid_id'],
            ['rental_financial_adjustments', 'maid_id'],
            ['return_requests', 'maid_id'],
            ['shelter_living_expense_allocations', 'maid_id'],
            ['transfer_service_contracts', 'maid_id'],
            ['travel_histories', 'maid_id'],
        ];
    }
}
