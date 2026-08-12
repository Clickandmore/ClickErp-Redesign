<?php

namespace App\Console\Commands;

use App\Services\commissions\LegacyCommissionMarketerResolver;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CleanupLegacyMarketerAdmins extends Command
{
    protected $signature = 'admins:cleanup-legacy-marketers
                            {--database=* : قاعدة بيانات محددة؛ يمكن تكرار الخيار}
                            {--all-databases : فحص كل قواعد MySQL المتاحة التي تحتوي جدول admins}
                            {--execute : تنفيذ التنظيف فعلياً؛ بدونه يعمل الأمر بوضع المعاينة}';

    protected $description = 'معاينة أو حذف حسابات المسوقين الوهمية التي أنشأها ترحيل 2026-07-23 بالخطأ';

    private const TEMP_CONNECTION = 'legacy_marketer_cleanup';

    /**
     * These are the columns changed by the faulty migration. They are also
     * cleaned when an old database no longer has its original foreign keys.
     */
    private const LEGACY_ASSIGNMENTS = [
        'contracts' => 'marketer_id',
        'orders' => 'customer_id',
        'order_details' => 'admin_id',
    ];

    public function handle(): int
    {
        if ($this->option('all-databases') && $this->option('database')) {
            $this->error('استخدم --all-databases أو --database، وليس كليهما معاً.');

            return self::INVALID;
        }

        $execute = (bool) $this->option('execute');
        $databases = $this->resolveDatabases();

        if ($databases === null) {
            return self::FAILURE;
        }

        if (! $execute) {
            $this->warn('وضع المعاينة: لن تُعدّل أي بيانات. أضف --execute بعد مراجعة النتائج.');
        }

        $failed = false;
        $totalProfiles = 0;
        $matchedDatabases = 0;

        foreach ($databases as $database) {
            try {
                $connection = $this->connectionFor($database);
                $result = $this->cleanupDatabase($connection, $database, $execute);
                $totalProfiles += $result['profiles'];
                $matchedDatabases += $result['profiles'] > 0 ? 1 : 0;
            } catch (Throwable $exception) {
                $failed = true;
                $this->error("[{$database}] فشل التنظيف: {$exception->getMessage()}");
            } finally {
                if ($database !== DB::connection()->getDatabaseName()) {
                    DB::purge(self::TEMP_CONNECTION);
                }
            }
        }

        $verb = $execute ? 'حُذفت' : 'عُثر على';
        $this->newLine();
        $this->info("{$verb} {$totalProfiles} حساباً مطابقاً للبصمة في {$matchedDatabases} قاعدة بيانات.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function resolveDatabases(): ?array
    {
        $requested = array_values(array_unique(array_filter(array_map(
            static fn ($database) => trim((string) $database),
            (array) $this->option('database')
        ))));

        if ($requested) {
            return $requested;
        }

        $default = DB::connection();
        if (! $this->option('all-databases')) {
            return [(string) $default->getDatabaseName()];
        }

        if ($default->getDriverName() !== 'mysql') {
            $this->error('الخيار --all-databases مدعوم لاتصال MySQL فقط.');

            return null;
        }

        return collect($default->select(
            "SELECT DISTINCT TABLE_SCHEMA AS database_name
             FROM information_schema.TABLES
             WHERE TABLE_NAME = 'admins'
               AND TABLE_SCHEMA NOT IN ('information_schema', 'mysql', 'performance_schema', 'sys')
             ORDER BY TABLE_SCHEMA"
        ))->map(static fn ($row) => (string) $row->database_name)->all();
    }

    private function connectionFor(string $database): Connection
    {
        $defaultName = (string) config('database.default');
        $default = DB::connection($defaultName);

        if ($database === (string) $default->getDatabaseName()) {
            return $default;
        }

        if ($default->getDriverName() !== 'mysql') {
            throw new \RuntimeException('اختيار قاعدة أخرى مدعوم لاتصال MySQL فقط.');
        }

        $available = collect($default->select('SHOW DATABASES'))
            ->flatMap(static fn ($row) => array_values((array) $row))
            ->map(static fn ($name) => (string) $name);

        if (! $available->containsStrict($database)) {
            throw new \RuntimeException('قاعدة البيانات غير موجودة أو لا يملك الاتصال صلاحية الوصول إليها.');
        }

        $configuration = (array) config("database.connections.{$defaultName}");
        $configuration['database'] = $database;
        config(['database.connections.' . self::TEMP_CONNECTION => $configuration]);
        DB::purge(self::TEMP_CONNECTION);

        return DB::connection(self::TEMP_CONNECTION);
    }

    private function cleanupDatabase(Connection $connection, string $database, bool $execute): array
    {
        $schema = Schema::connection($connection->getName());
        if (! $schema->hasTable('admins') || ! $schema->hasColumn('admins', 'username') || ! $schema->hasColumn('admins', 'email')) {
            $this->line("[{$database}] تم التجاوز: جدول admins أو أعمدة البصمة غير موجودة.");

            return ['profiles' => 0];
        }

        $profiles = $this->matchingProfiles($connection);
        if ($profiles->isEmpty()) {
            $this->line("[{$database}] لا توجد حسابات مطابقة.");

            return ['profiles' => 0];
        }

        $ids = $profiles->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $references = $this->referenceCounts($connection, $schema, $ids);

        $this->newLine();
        $this->info("[{$database}] الحسابات المطابقة: " . count($ids));
        $this->table(['ID', 'الاسم', 'اسم المستخدم'], $profiles->map(static fn ($profile) => [
            $profile->id,
            $profile->name,
            $profile->username,
        ])->all());

        if ($references) {
            $this->table(['الجدول.العمود', 'السجلات المرتبطة', 'الإجراء'], array_map(static fn ($reference) => [
                $reference['table'] . '.' . $reference['column'],
                $reference['count'],
                $reference['nullable'] ? 'تعيين NULL' : 'حسب قيد قاعدة البيانات',
            ], $references));
        }

        if ($execute) {
            $connection->transaction(function () use ($connection, $ids, $references) {
                foreach ($references as $reference) {
                    if (! $reference['nullable']) {
                        continue;
                    }

                    $connection->table($reference['table'])
                        ->whereIn($reference['column'], $ids)
                        ->update([$reference['column'] => null]);
                }

                $connection->table('admins')->whereIn('id', $ids)->delete();
            });

            $this->info("[{$database}] اكتمل التنظيف.");
        }

        return ['profiles' => count($ids)];
    }

    private function matchingProfiles(Connection $connection)
    {
        return $connection->table('admins')
            ->where(function ($query) {
                foreach (LegacyCommissionMarketerResolver::HISTORICAL_PROFILE_NAMES as $name) {
                    $key = substr(hash('sha256', $name), 0, 16);
                    $query->orWhere(function ($profileQuery) use ($name, $key) {
                        $profileQuery->where('name', $name)
                            ->where('username', 'legacy_marketer_' . $key)
                            ->where('email', "legacy-marketer-{$key}@invalid.local");
                    });
                }
            })
            ->orderBy('id')
            ->get(['id', 'name', 'username']);
    }

    private function referenceCounts(Connection $connection, $schema, array $ids): array
    {
        $references = [];

        foreach (self::LEGACY_ASSIGNMENTS as $table => $column) {
            if (! $schema->hasTable($table) || ! $schema->hasColumn($table, $column)) {
                continue;
            }

            $references[$table . '.' . $column] = [
                'table' => $table,
                'column' => $column,
                'nullable' => $this->columnIsNullable($connection, $table, $column),
                'count' => $connection->table($table)->whereIn($column, $ids)->count(),
            ];
        }

        if ($connection->getDriverName() === 'mysql') {
            foreach ($this->foreignKeyReferences($connection) as $reference) {
                $key = $reference['table'] . '.' . $reference['column'];
                if (isset($references[$key])) {
                    continue;
                }

                $reference['count'] = $connection->table($reference['table'])
                    ->whereIn($reference['column'], $ids)
                    ->count();
                $references[$key] = $reference;
            }
        }

        return array_values(array_filter($references, static fn ($reference) => $reference['count'] > 0));
    }

    private function columnIsNullable(Connection $connection, string $table, string $column): bool
    {
        if ($connection->getDriverName() !== 'mysql') {
            return true;
        }

        $row = $connection->selectOne(
            'SELECT IS_NULLABLE AS is_nullable FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$connection->getDatabaseName(), $table, $column]
        );

        return $row && strtoupper((string) $row->is_nullable) === 'YES';
    }

    private function foreignKeyReferences(Connection $connection): array
    {
        return collect($connection->select(
            "SELECT k.TABLE_NAME AS table_name, k.COLUMN_NAME AS column_name,
                    c.IS_NULLABLE AS is_nullable
             FROM information_schema.KEY_COLUMN_USAGE k
             INNER JOIN information_schema.COLUMNS c
               ON c.TABLE_SCHEMA = k.TABLE_SCHEMA
              AND c.TABLE_NAME = k.TABLE_NAME
              AND c.COLUMN_NAME = k.COLUMN_NAME
             WHERE k.TABLE_SCHEMA = ?
               AND k.REFERENCED_TABLE_NAME = 'admins'
               AND k.REFERENCED_COLUMN_NAME = 'id'",
            [$connection->getDatabaseName()]
        ))->map(static fn ($row) => [
            'table' => (string) $row->table_name,
            'column' => (string) $row->column_name,
            'nullable' => strtoupper((string) $row->is_nullable) === 'YES',
        ])->all();
    }
}
