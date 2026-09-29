<?php

namespace App\Console\Commands;

use App\Models\auth\Admin;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class RepairAymanActivityIncident extends Command
{
    protected $signature = 'data-repair:ayman-activity-incident
        {--apply : Apply the repair; without it the command is read-only}';

    protected $description = 'Reverse the verified destructive activity-log incident performed by admin #115';

    private const ACTOR_ID = 115;
    private const FIRST_ACTIVITY_ID = 25000;
    private const LAST_ACTIVITY_ID = 25103;
    private const CONSEQUENCE_ACTIVITY_IDS = [25116, 25117, 25118];
    private const IGNORED_FIELDS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * Rows lost through database-level cascade when WhatDo #1 and #2 were deleted.
     * These values come from the 2026-09-08 local backup and are corroborated by
     * the parent rows' unchanged June timestamps in activity records 25066-25067.
     */
    private const CASCADE_ROWS = [
        'what_do_translations' => [
            ['id' => 1, 'what_do_id' => 1, 'locale' => 'ar', 'title' => 'استقدام العمالة', 'description' => 'نوفر لك أفضل المرشحين حسب احتياجك مع إجراءات موثقة وسريعة من بداية الطلب حتى وصول العامل.', 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'what_do_id' => 1, 'locale' => 'en', 'title' => 'Recruitment of workers', 'description' => 'We provide you with the best candidates according to your needs, with documented and fast procedures from the beginning of the request until the worker arrives.', 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'what_do_id' => 2, 'locale' => 'ar', 'title' => 'تأجير العمالة المنزلية', 'description' => 'حلول تأجير مرنة للعمالة المنزلية بعقود واضحة ومدة مناسبة لتغطية احتياج الأسرة اليومي بثقة وراحة.', 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'what_do_id' => 2, 'locale' => 'en', 'title' => 'Domestic worker hire', 'description' => "Flexible domestic worker rental solutions with clear contracts and suitable durations to confidently and comfortably cover the family's daily needs.", 'created_at' => null, 'updated_at' => null],
        ],
        'what_do_items' => [
            ['id' => 1, 'what_do_id' => 1, 'created_at' => '2026-06-08 14:25:18', 'updated_at' => '2026-06-08 14:25:18'],
            ['id' => 2, 'what_do_id' => 2, 'created_at' => '2026-06-08 14:26:13', 'updated_at' => '2026-06-08 14:26:13'],
        ],
        'what_do_item_translations' => [
            ['id' => 1, 'what_do_item_id' => 1, 'locale' => 'ar', 'title' => '-', 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'what_do_item_id' => 1, 'locale' => 'en', 'title' => '-', 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'what_do_item_id' => 2, 'locale' => 'ar', 'title' => '-', 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'what_do_item_id' => 2, 'locale' => 'en', 'title' => '-', 'created_at' => null, 'updated_at' => null],
        ],
    ];

    public function handle(): int
    {
        try {
            $plan = $this->buildPlan();
            $this->renderPlan($plan);

            if (!$this->option('apply')) {
                $this->info('Dry run only. Re-run with --apply to execute this verified plan.');
                return self::SUCCESS;
            }

            if ($plan['conflicts'] !== []) {
                throw new RuntimeException('Conflicts were found; refusing to change the database.');
            }

            DB::transaction(fn () => $this->applyPlan($plan), 3);
            $this->verify($plan);

            activity('data_repair')
                ->event('incident_recovered')
                ->withProperties([
                    'repair' => 'ayman_activity_incident',
                    'actor_id' => self::ACTOR_ID,
                    'activity_range' => [self::FIRST_ACTIVITY_ID, self::LAST_ACTIVITY_ID],
                    'consequence_activity_ids' => self::CONSEQUENCE_ACTIVITY_IDS,
                    'updated_fields_restored' => count($plan['updates']),
                    'created_rows_removed' => count($plan['deletes']),
                    'deleted_rows_restored' => count($plan['inserts']),
                    'cascade_rows_restored' => count($plan['cascade_inserts']),
                    'protected_fields' => count($plan['protected']),
                ])
                ->log('Verified destructive activity by admin #115 was reversed');

            $this->info('Incident data was restored and verified successfully.');
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            report($exception);
            return self::FAILURE;
        }
    }

    private function buildPlan(): array
    {
        $incident = DB::table('activity_log')
            ->whereBetween('id', [self::FIRST_ACTIVITY_ID, self::LAST_ACTIVITY_ID])
            ->where('causer_type', Admin::class)
            ->where('causer_id', self::ACTOR_ID)
            ->orderBy('id')
            ->get();

        if ($incident->count() !== 104
            || (int) $incident->first()?->id !== self::FIRST_ACTIVITY_ID
            || (int) $incident->last()?->id !== self::LAST_ACTIVITY_ID) {
            throw new RuntimeException('The verified incident activity range is incomplete or has changed.');
        }

        $consequences = DB::table('activity_log')
            ->whereIn('id', self::CONSEQUENCE_ACTIVITY_IDS)
            ->orderBy('id')
            ->get();

        if ($consequences->count() !== count(self::CONSEQUENCE_ACTIVITY_IDS)) {
            throw new RuntimeException('The verified consequence activity records are incomplete.');
        }

        $activities = $incident->concat($consequences)->sortBy('id')->values();
        $affected = $activities
            ->filter(fn ($row) => $row->subject_type && $row->subject_id)
            ->map(fn ($row) => ['subject_type' => $row->subject_type, 'subject_id' => $row->subject_id])
            ->unique(fn ($row) => $this->subjectKey($row['subject_type'], $row['subject_id']))
            ->values();

        $protected = $this->laterProtectedFields($affected);
        $updates = [];
        $created = [];
        $deleted = [];

        foreach ($activities as $activity) {
            if (!$activity->subject_type || !$activity->subject_id || !class_exists($activity->subject_type)) {
                continue;
            }

            $key = $this->subjectKey($activity->subject_type, $activity->subject_id);
            $properties = json_decode((string) $activity->properties, true) ?: [];
            $event = strtolower((string) $activity->event);

            if ($event === 'updated') {
                $old = Arr::except((array) ($properties['old'] ?? []), self::IGNORED_FIELDS);
                $new = Arr::except((array) ($properties['attributes'] ?? []), self::IGNORED_FIELDS);

                foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
                    if (isset($protected[$key]['*']) || isset($protected[$key][$field])) {
                        continue;
                    }

                    $updateKey = $key . '|' . $field;
                    $updates[$updateKey] ??= [
                        'model' => $activity->subject_type,
                        'id' => (int) $activity->subject_id,
                        'field' => $field,
                        'target' => $old[$field] ?? null,
                        'first_activity_id' => (int) $activity->id,
                    ];
                    $updates[$updateKey]['expected'] = $new[$field] ?? null;
                    $updates[$updateKey]['last_activity_id'] = (int) $activity->id;
                }
            } elseif ($event === 'created') {
                $created[$key] = [
                    'model' => $activity->subject_type,
                    'id' => (int) $activity->subject_id,
                    'attributes' => (array) ($properties['attributes'] ?? []),
                    'activity_id' => (int) $activity->id,
                ];
            } elseif (in_array($event, ['deleted', 'force_deleted'], true)) {
                $deleted[$key] = [
                    'model' => $activity->subject_type,
                    'id' => (int) $activity->subject_id,
                    'attributes' => (array) ($properties['old'] ?? []),
                    'activity_id' => (int) $activity->id,
                ];
            }
        }

        // The automatic cancellation was a direct consequence of the incident's
        // injected deadline, but deadline_at itself was omitted from the first log.
        $orderKey = $this->subjectKey(\App\Models\ingaz\Order::class, 624);
        $updates[$orderKey . '|deadline_at'] = [
            'model' => \App\Models\ingaz\Order::class,
            'id' => 624,
            'field' => 'deadline_at',
            'target' => null,
            'expected' => null,
            'first_activity_id' => 25023,
            'last_activity_id' => 25116,
        ];

        // The imported database uses local SQL dates while activity properties
        // serialize dates as UTC. Pin the two incident-consequence comparisons
        // to their verified raw database representations.
        if (isset($updates[$orderKey . '|status_changed_at'])) {
            $updates[$orderKey . '|status_changed_at']['expected'] = '2026-09-30 01:00:02';
        }
        $maidKey = $this->subjectKey(\App\Models\ingaz\Maid::class, 893);
        if (isset($updates[$maidKey . '|available_at'])) {
            $updates[$maidKey . '|available_at']['expected'] = '2026-09-29';
            $updates[$maidKey . '|available_at']['target'] = '2026-09-14';
        }

        foreach ([1 => '2026-06-08 14:25:18', 2 => '2026-06-08 14:26:13'] as $id => $timestamp) {
            $key = $this->subjectKey(\App\Models\WhatDo::class, $id);
            if (isset($deleted[$key])) {
                $deleted[$key]['attributes']['created_at'] = $timestamp;
                $deleted[$key]['attributes']['updated_at'] = $timestamp;
            }
        }

        $plan = [
            'updates' => array_values($updates),
            'deletes' => [],
            'inserts' => [],
            'cascade_inserts' => [],
            'protected' => $this->flattenProtected($protected),
            'already_restored' => [],
            'conflicts' => [],
        ];

        foreach ($plan['updates'] as $index => $change) {
            $state = $this->classifyUpdate($change);
            if ($state === 'target') {
                $plan['already_restored'][] = "{$change['model']}#{$change['id']}.{$change['field']}";
                unset($plan['updates'][$index]);
            } elseif ($state !== 'expected') {
                $plan['conflicts'][] = "Unexpected current value for {$change['model']}#{$change['id']}.{$change['field']}";
            }
        }
        $plan['updates'] = array_values($plan['updates']);

        foreach ($created as $key => $row) {
            if (isset($protected[$key]['*'])) {
                continue;
            }

            $state = $this->classifyCreatedRow($row);
            if ($state === 'absent') {
                $plan['already_restored'][] = $key;
            } elseif ($state === 'expected') {
                $plan['deletes'][] = $row;
            } else {
                $plan['conflicts'][] = "Created row {$key} changed after the incident without an auditable match ({$state})";
            }
        }

        foreach ($deleted as $key => $row) {
            $model = new $row['model'];
            $exists = DB::table($model->getTable())->where($model->getKeyName(), $row['id'])->exists();
            if ($exists) {
                $plan['already_restored'][] = $key;
            } else {
                $plan['inserts'][] = $row;
            }
        }

        foreach (self::CASCADE_ROWS as $table => $rows) {
            foreach ($rows as $row) {
                $current = DB::table($table)->where('id', $row['id'])->first();
                if (!$current) {
                    $plan['cascade_inserts'][] = ['table' => $table, 'attributes' => $row];
                } elseif (($field = $this->firstDifferentField((array) $current, $row)) !== null) {
                    $plan['conflicts'][] = "Cascade row {$table}#{$row['id']} already exists with different data ({$field})";
                }
            }
        }

        sort($plan['protected']);
        return $plan;
    }

    private function laterProtectedFields($affected): array
    {
        $affectedKeys = $affected->mapWithKeys(fn ($row) => [
            $this->subjectKey($row['subject_type'], $row['subject_id']) => true,
        ])->all();
        $protected = [];

        $later = DB::table('activity_log')
            ->where('id', '>', self::LAST_ACTIVITY_ID)
            ->whereNotIn('id', self::CONSEQUENCE_ACTIVITY_IDS)
            ->orderBy('id')
            ->get();

        foreach ($later as $activity) {
            $key = $this->subjectKey((string) $activity->subject_type, $activity->subject_id);
            if (!isset($affectedKeys[$key])) {
                continue;
            }

            $properties = json_decode((string) $activity->properties, true) ?: [];
            $event = strtolower((string) $activity->event);
            if ($event !== 'updated') {
                $protected[$key]['*'] = true;
                continue;
            }

            $fields = array_unique(array_merge(
                array_keys((array) ($properties['old'] ?? [])),
                array_keys((array) ($properties['attributes'] ?? []))
            ));
            foreach (array_diff($fields, self::IGNORED_FIELDS) as $field) {
                $protected[$key][$field] = true;
            }
        }

        return $protected;
    }

    private function classifyUpdate(array $change): string
    {
        $model = new $change['model'];
        $row = DB::table($model->getTable())->where($model->getKeyName(), $change['id'])->first();
        if (!$row) {
            return 'missing';
        }

        $current = (array) $row;
        if (!array_key_exists($change['field'], $current)) {
            return 'missing_field';
        }

        $expected = $this->toRawValue($model, $change['field'], $change['expected']);
        $target = $this->toRawValue($model, $change['field'], $change['target']);

        if ($this->valuesEqual($current[$change['field']], $target)) {
            return 'target';
        }

        return $this->valuesEqual($current[$change['field']], $expected) ? 'expected' : 'conflict';
    }

    private function classifyCreatedRow(array $row): string
    {
        $model = new $row['model'];
        $current = DB::table($model->getTable())->where($model->getKeyName(), $row['id'])->first();
        if (!$current) {
            return 'absent';
        }

        $columns = array_flip(Schema::getColumnListing($model->getTable()));
        $expected = array_intersect_key(Arr::except($row['attributes'], ['created_at', 'updated_at']), $columns);
        if ($row['model'] === \App\Models\ingaz\Notification::class) {
            $expected = Arr::except($expected, ['title', 'description']);
        }
        foreach ($expected as $field => $value) {
            if (!$this->valuesEqual(((array) $current)[$field] ?? null, $this->toRawValue($model, $field, $value))) {
                return 'conflict:' . $field;
            }
        }

        return 'expected';
    }

    private function applyPlan(array $plan): void
    {
        foreach ($plan['deletes'] as $row) {
            $model = new $row['model'];
            DB::table($model->getTable())->where($model->getKeyName(), $row['id'])->delete();
        }

        foreach ($plan['inserts'] as $row) {
            $model = new $row['model'];
            $columns = array_flip(Schema::getColumnListing($model->getTable()));
            $attributes = array_intersect_key($row['attributes'], $columns);
            $raw = [];
            foreach ($attributes as $field => $value) {
                $raw[$field] = $this->toRawValue($model, $field, $value);
            }
            DB::table($model->getTable())->insert($raw);
        }

        foreach ($plan['cascade_inserts'] as $row) {
            DB::table($row['table'])->insert($row['attributes']);
        }

        foreach ($plan['updates'] as $change) {
            $model = new $change['model'];
            DB::table($model->getTable())
                ->where($model->getKeyName(), $change['id'])
                ->update([$change['field'] => $this->toRawValue($model, $change['field'], $change['target'])]);
        }
    }

    private function verify(array $plan): void
    {
        foreach ($plan['updates'] as $change) {
            if ($this->classifyUpdate(array_replace($change, ['expected' => $change['target']])) !== 'target') {
                throw new RuntimeException("Verification failed for {$change['model']}#{$change['id']}.{$change['field']}");
            }
        }

        foreach ($plan['deletes'] as $row) {
            $model = new $row['model'];
            if (DB::table($model->getTable())->where($model->getKeyName(), $row['id'])->exists()) {
                throw new RuntimeException("Verification failed: {$row['model']}#{$row['id']} still exists");
            }
        }

        foreach (array_merge($plan['inserts'], array_map(fn ($row) => [
            'model' => null,
            'id' => $row['attributes']['id'],
            'table' => $row['table'],
        ], $plan['cascade_inserts'])) as $row) {
            $table = $row['table'] ?? (new $row['model'])->getTable();
            if (!DB::table($table)->where('id', $row['id'])->exists()) {
                throw new RuntimeException("Verification failed: {$table}#{$row['id']} was not restored");
            }
        }
    }

    private function renderPlan(array $plan): void
    {
        $this->table(['Action', 'Count'], [
            ['Restore changed fields', count($plan['updates'])],
            ['Remove incident-created rows', count($plan['deletes'])],
            ['Restore explicitly deleted rows', count($plan['inserts'])],
            ['Restore cascade-deleted rows', count($plan['cascade_inserts'])],
            ['Keep later staff changes', count($plan['protected'])],
            ['Already restored', count($plan['already_restored'])],
            ['Conflicts', count($plan['conflicts'])],
        ]);

        foreach ($plan['protected'] as $item) {
            $this->line("PROTECTED: {$item}");
        }
        foreach ($plan['conflicts'] as $conflict) {
            $this->error("CONFLICT: {$conflict}");
        }
    }

    private function toRawValue(Model $model, string $field, mixed $value): mixed
    {
        $copy = clone $model;
        $copy->setAttribute($field, $value);
        return $copy->getAttributes()[$field] ?? null;
    }

    private function firstDifferentField(array $current, array $expected): ?string
    {
        foreach ($expected as $field => $value) {
            if (!$this->valuesEqual($current[$field] ?? null, $value)) {
                return $field;
            }
        }
        return null;
    }

    private function valuesEqual(mixed $left, mixed $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        if (is_bool($left) || is_bool($right)) {
            return (int) $left === (int) $right;
        }

        if (is_string($left) && is_string($right)
            && preg_match('/^\d{4}-\d{2}-\d{2}(?: 00:00:00)?$/', $left)
            && preg_match('/^\d{4}-\d{2}-\d{2}(?: 00:00:00)?$/', $right)) {
            return substr($left, 0, 10) === substr($right, 0, 10);
        }

        foreach ([&$left, &$right] as &$value) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                    $value = $decoded;
                }
            }
        }
        unset($value);

        return is_array($left) || is_array($right)
            ? json_encode($left, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === json_encode($right, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : (string) $left === (string) $right;
    }

    private function flattenProtected(array $protected): array
    {
        $flat = [];
        foreach ($protected as $subject => $fields) {
            foreach (array_keys($fields) as $field) {
                $flat[] = $subject . '.' . $field;
            }
        }
        return $flat;
    }

    private function subjectKey(string $type, mixed $id): string
    {
        return $type . '#' . (int) $id;
    }
}
