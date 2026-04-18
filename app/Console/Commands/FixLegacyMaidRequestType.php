<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

class FixLegacyMaidRequestType extends Command
{
    protected $signature = 'maids:fix-legacy-request-type
                            {--before-date=2025-10-20 : Backfill legacy request types for CVs created before this date}
                            {--dry-run : Show what would be updated without applying changes}';

    protected $description = 'Backfill legacy maid request_type values to custom_spec for older CVs';

    public function handle(): int
    {
        if (!Schema::hasColumn('maids', 'request_type')) {
            $this->warn('Column maids.request_type does not exist.');

            return self::SUCCESS;
        }

        $beforeDate = CarbonImmutable::parse((string) $this->option('before-date'))->startOfDay();
        $activityTable = config('activitylog.table_name', 'activity_log');

        $candidateQuery = Maid::query()
            ->where(function ($query) {
                $query->where('request_type', Maid::REQUEST_TYPE_KNOWN)
                    ->orWhere('request_type', '')
                    ->orWhereNull('request_type');
            })
            ->whereDate('created_at', '<', $beforeDate->toDateString());

        $activityExcludedIds = collect();
        if (Schema::hasTable($activityTable)) {
            $activityExcludedIds = Activity::query()
                ->where('subject_type', Maid::class)
                ->where('created_at', '>=', $beforeDate->toDateTimeString())
                ->where('properties', 'like', '%request_type%')
                ->distinct()
                ->pluck('subject_id');

            if ($activityExcludedIds->isNotEmpty()) {
                $candidateQuery->whereNotIn('id', $activityExcludedIds);
            }
        }

        $totalCandidates = (clone $candidateQuery)->count();

        $this->info("Legacy candidate rows: {$totalCandidates}");
        $this->line('Cutoff date: ' . $beforeDate->toDateString());
        $this->line('Excluded rows with explicit request_type activity: ' . $activityExcludedIds->count());

        $sampleRows = (clone $candidateQuery)
            ->orderBy('created_at')
            ->limit(10)
            ->get(['id', 'passport_num', 'created_at', 'updated_at']);

        if ($sampleRows->isEmpty()) {
            $this->info('No rows need correction.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'passport', 'created_at', 'updated_at'],
            $sampleRows->map(fn (Maid $maid) => [
                'id' => $maid->id,
                'passport' => $maid->passport_num,
                'created_at' => optional($maid->created_at)->toDateTimeString(),
                'updated_at' => optional($maid->updated_at)->toDateTimeString(),
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No data was changed.');

            return self::SUCCESS;
        }

        $updated = 0;
        (clone $candidateQuery)
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($maids) use (&$updated) {
                $ids = $maids->pluck('id')->all();
                if ($ids === []) {
                    return;
                }

                $updated += Maid::query()
                    ->whereIn('id', $ids)
                    ->update(['request_type' => Maid::REQUEST_TYPE_CUSTOM]);
            });

        $this->info("Updated rows: {$updated}");

        return self::SUCCESS;
    }
}
