<?php

namespace App\Console\Commands;

use App\Models\auth\Admin;
use App\Models\ContractStatusLog;
use App\Models\DeportationProcedureLog;
use App\Models\FinancialRequestStatusLog;
use App\Support\Audit\AuditProperties;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\ConstraintLog;

class MergeDomainActivityLogs extends Command
{
    protected $signature = 'activitylog:merge-domain {--apply : Persist the merge; otherwise preview only}';
    protected $description = 'Merge existing domain history into the activity log, preserving source IDs and timestamps.';

    public const SOURCES = [
        'contract_status_logs' => [ContractStatusLog::class, 'performed_by_id', 'notes'],
        'deportation_procedure_logs' => [DeportationProcedureLog::class, 'admin_id', 'description'],
        'financial_request_status_logs' => [FinancialRequestStatusLog::class, 'performed_by_id', 'remarks'],
        'fn_constraint_logs' => [ConstraintLog::class, 'admin_id', 'description'],
    ];

    public function handle(): int
    {
        $table = config('activitylog.table_name', 'activity_log');
        if (!Schema::hasColumn($table, 'legacy_source') || !Schema::hasColumn($table, 'legacy_id')) {
            $this->error('Missing legacy_source / legacy_id columns. Run the legacy activity migration first.');
            return 1;
        }
        foreach (self::SOURCES as $source => [$model, $actorColumn, $descriptionColumn]) {
            if (!Schema::hasTable($source)) {
                continue;
            }
            $created = $existing = $invalid = 0;
            DB::table($source)->orderBy('id')->chunkById(250, function ($rows) use ($table, $source, $model, $actorColumn, $descriptionColumn, &$created, &$existing, &$invalid) {
                foreach ($rows as $row) {
                    $already = DB::table($table)->where(function ($query) use ($source, $row, $model) {
                        $query->where(function ($query) use ($source, $row) {
                            $query->where('legacy_source', 'domain:' . $source)->where('legacy_id', $row->id);
                        })->orWhere(function ($query) use ($model, $row) {
                            $query->where('subject_type', $model)->where('subject_id', $row->id)->where('event', 'created');
                        });
                    })->exists();
                    if ($already) {
                        $existing++;
                        continue;
                    }
                    if (!$row->created_at || substr($row->created_at, 0, 4) < '1900') {
                        $invalid++;
                        continue;
                    }
                    $created++;
                    if (!$this->option('apply')) {
                        continue;
                    }
                    $actorId = $row->{$actorColumn} ?? null;
                    // No actor is invented when historical data has no performer.
                    $payload = [
                        'log_name' => 'domain_history',
                        'description' => $row->{$descriptionColumn} ?: 'سجل تاريخي محفوظ من سجلات النظام التفصيلية',
                        'subject_type' => $model,
                        'subject_id' => $row->id,
                        'event' => 'created',
                        'causer_type' => $actorId ? Admin::class : null,
                        'causer_id' => $actorId,
                        'properties' => json_encode(AuditProperties::sanitize([
                            'attributes' => (array) $row,
                            'historical_import' => true,
                            'source_table' => $source,
                            'source_event' => $row->action ?? $row->action_type ?? 'status_changed',
                        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                        'legacy_source' => 'domain:' . $source,
                        'legacy_id' => $row->id,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->created_at,
                    ];
                    // The unique source/id index makes retries and concurrent imports safe.
                    DB::table($table)->upsert([$payload], ['legacy_source', 'legacy_id'], ['legacy_id']);
                }
            });
            $this->line("{$source}: new={$created}, existing={$existing}, invalid_date={$invalid}");
        }
        $this->info($this->option('apply') ? 'Merge completed; source tables preserved.' : 'Preview only. Use --apply to persist.');
        return 0;
    }
}
