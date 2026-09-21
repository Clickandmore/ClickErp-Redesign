<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReleaseOrphanedMaidReservations extends Command
{
    protected $signature = 'maids:release-orphaned-reservations
        {--apply : Apply repairs; without this option only preview}
        {--passport=* : Limit to these passport numbers}';

    protected $description = 'Preview or release reserved CVs with no assignment history or unavailability records';

    private array $blockers = [];

    public function handle(): int
    {
        // Required tables must exist: never silently omit the primary assignment checks.
        $this->blockers = [
            'contracts' => 'maid_id',
            'orders' => 'maid_id',
            'rental_contracts' => 'maid_id',
            'maid_unavailable_records' => 'maid_id',
        ];
        foreach ($this->blockers as $table => $column) {
            if (!Schema::hasColumn($table, $column)) {
                $this->error("Required assignment table/column missing: {$table}.{$column}");
                return 1;
            }
        }
        foreach ([
            'maid_status_change_requests' => 'maid_id',
            'transfer_service_contracts' => 'maid_id',
            'istiqdam_contracts' => 'assigned_maid_id',
            'deportation_procedures' => 'maid_id',
            'travel_histories' => 'maid_id',
            'maid_recruitment_procedures' => 'maid_id',
        ] as $table => $column) {
            if (Schema::hasTable($table)) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->error("Assignment column missing: {$table}.{$column}");
                    return 1;
                }
                $this->blockers[$table] = $column;
            }
        }

        $apply = (bool) $this->option('apply');
        $count = 0;
        $this->info($apply ? 'Applying repairs across all branches.' : 'Preview only; no records will be changed.');
        $this->candidates()->select('maids.id', 'maids.passport_num')->chunkById(200, function ($maids) use ($apply, &$count) {
            foreach ($maids as $maid) {
                if (!$apply) {
                    $this->line("{$maid->id}\t{$maid->passport_num}");
                    $count++;
                    continue;
                }
                $changed = DB::transaction(function () use ($maid) {
                    $before = DB::table('maids')->where('id', $maid->id)->lockForUpdate()
                        ->first(['id', 'passport_num', 'status', 'available', 'updated_at']);
                    // Recheck all guards in the UPDATE, including unscoped assignment history.
                    $updated = $this->candidates()->where('maids.id', $maid->id)->update([
                        'status' => 'available', 'available' => true, 'updated_at' => now(),
                    ]);
                    if ($updated) {
                        Log::info('Released orphaned maid reservation', [
                            'command' => $this->getName(), 'before' => (array) $before,
                            'after' => ['status' => 'available', 'available' => true],
                        ]);
                    }
                    return $updated;
                });
                $count += $changed;
                $this->line("{$maid->id}\t{$maid->passport_num}\t" . ($changed ? 'repaired' : 'skipped: state changed'));
            }
        }, 'maids.id', 'id');
        $this->info(($apply ? 'Repaired' : 'Eligible') . ": {$count}");
        return 0;
    }

    private function candidates(): Builder
    {
        // Query builder deliberately includes links hidden by branch/soft-delete scopes.
        $query = DB::table('maids')->whereNull('maids.deleted_at')
            ->where('maids.status', 'reserved')->where('maids.available', false)
            ->where(function (Builder $q) {
                $q->whereNull('maids.back_out_reason')->orWhereRaw("TRIM(maids.back_out_reason) = ''");
            });
        if ($passports = $this->option('passport')) {
            $query->whereIn('maids.passport_num', $passports);
        }
        foreach ($this->blockers as $table => $column) {
            $query->whereNotExists(function (Builder $q) use ($table, $column) {
                $q->selectRaw('1')->from($table)->whereColumn("{$table}.{$column}", 'maids.id');
            });
        }
        return $query;
    }
}
