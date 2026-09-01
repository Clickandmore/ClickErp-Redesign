<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class RepairDuplicateConstraintCodes extends Command
{
    protected $signature = 'finance:repair-duplicate-constraint-codes
        {--database= : Refuse to run unless the current database has this exact name}
        {--apply : Apply the proposed renumbering; otherwise run as a read-only preview}
        {--force : Skip the production confirmation prompt}';

    protected $description = 'Preview or safely renumber active constraints that have duplicate codes';

    public function handle(): int
    {
        try {
            $this->guardDatabase();

            $preview = $this->buildPlan($this->constraintRows());
            $this->renderSummary($preview);

            if ($preview->isEmpty()) {
                $this->info('No active duplicate constraint codes were found.');

                return self::SUCCESS;
            }

            if (!$this->option('apply')) {
                $this->warn('Preview only: no data was changed. Re-run with --apply after taking a database backup.');

                return self::SUCCESS;
            }

            if (!$this->option('force') && !$this->confirm(
                'Renumber the duplicate constraints shown above in database [' . DB::getDatabaseName() . ']?'
            )) {
                $this->warn('No data was changed.');

                return self::SUCCESS;
            }

            $applied = DB::transaction(function (): Collection {
                // Lock the complete number space while the plan is recalculated.
                // This command should still be run during maintenance mode so web
                // requests do not wait behind this lock.
                $rows = DB::table('fn_constraints')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'code', 'deleted_at']);

                $plan = $this->buildPlan($rows);
                $now = now();

                foreach ($plan as $change) {
                    $updated = DB::table('fn_constraints')
                        ->where('id', $change['constraint_id'])
                        ->whereNull('deleted_at')
                        ->where('code', $change['old_code'])
                        ->update([
                            'code' => $change['new_code'],
                            'updated_at' => $now,
                        ]);

                    if ($updated !== 1) {
                        throw new RuntimeException(
                            "Constraint {$change['constraint_id']} changed while duplicates were being repaired."
                        );
                    }
                }

                if ($plan->isNotEmpty() && Schema::hasTable('fn_constraint_logs')) {
                    DB::table('fn_constraint_logs')->insert($plan->map(fn (array $change): array => [
                        'constraint_id' => $change['constraint_id'],
                        'admin_id' => null,
                        'action' => 'duplicate_code_renumbered',
                        'description' => 'Duplicate constraint code renumbered by maintenance command.',
                        'metadata' => json_encode([
                            'old_code' => $change['old_code'],
                            'new_code' => $change['new_code'],
                            'kept_by_constraint_id' => $change['keeper_id'],
                            'database' => DB::getDatabaseName(),
                        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'ip_address' => null,
                        'user_agent' => 'artisan finance:repair-duplicate-constraint-codes',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all());
                }

                if (Schema::hasTable('fn_constraint_code_sequences')) {
                    DB::table('fn_constraint_code_sequences')
                        ->where('id', 1)
                        ->update([
                            'last_code' => (int) DB::table('fn_constraints')->max('code'),
                            'updated_at' => $now,
                        ]);
                }

                if ($this->activeDuplicateCodeCount() !== 0) {
                    throw new RuntimeException('Duplicate constraint codes remain; all changes were rolled back.');
                }

                return $plan;
            }, 5);

            $reportPath = $this->writeReport($applied);
            $this->info("Renumbered {$applied->count()} duplicate constraints successfully.");
            $this->line('Audit report: storage/app/' . $reportPath);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function guardDatabase(): void
    {
        if (!Schema::hasTable('fn_constraints')) {
            throw new RuntimeException('The fn_constraints table does not exist.');
        }

        $expected = trim((string) $this->option('database'));
        $actual = DB::getDatabaseName();

        if ($expected !== '' && $expected !== $actual) {
            throw new RuntimeException("Current database is [{$actual}], not [{$expected}]. No data was changed.");
        }
    }

    private function constraintRows(): Collection
    {
        return DB::table('fn_constraints')
            ->orderBy('id')
            ->get(['id', 'code', 'deleted_at']);
    }

    private function buildPlan(Collection $rows): Collection
    {
        $highestCode = (int) $rows->max('code');
        $nextCode = $highestCode + 1;

        return $rows
            ->whereNull('deleted_at')
            ->groupBy(fn ($row) => (string) $row->code)
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->sortKeys(SORT_NUMERIC)
            ->flatMap(function (Collection $group) use (&$nextCode): array {
                $ordered = $group->sortBy('id')->values();
                $keeper = $ordered->first();

                return $ordered->slice(1)->map(function ($row) use ($keeper, &$nextCode): array {
                    return [
                        'old_code' => (int) $row->code,
                        'keeper_id' => (int) $keeper->id,
                        'constraint_id' => (int) $row->id,
                        'new_code' => $nextCode++,
                    ];
                })->all();
            })
            ->values();
    }

    private function renderSummary(Collection $plan): void
    {
        $this->table(
            ['Old code', 'Kept constraint ID', 'Renumbered constraint ID', 'New code'],
            $plan->map(fn (array $change) => [
                $change['old_code'],
                $change['keeper_id'],
                $change['constraint_id'],
                $change['new_code'],
            ])->all()
        );

        $this->line('Database: ' . DB::getDatabaseName());
        $this->line('Duplicate codes: ' . $plan->pluck('old_code')->unique()->count());
        $this->line('Constraints to renumber: ' . $plan->count());
    }

    private function activeDuplicateCodeCount(): int
    {
        return DB::query()
            ->fromSub(
                DB::table('fn_constraints')
                    ->whereNull('deleted_at')
                    ->select('code')
                    ->groupBy('code')
                    ->havingRaw('COUNT(*) > 1'),
                'duplicates'
            )
            ->count();
    }

    private function writeReport(Collection $changes): string
    {
        $path = 'finance/constraint-renumbering/' . now()->format('Ymd-His') . '.json';

        Storage::disk('local')->put($path, json_encode([
            'database' => DB::getDatabaseName(),
            'executed_at' => now()->toIso8601String(),
            'changes' => $changes->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return $path;
    }
}
