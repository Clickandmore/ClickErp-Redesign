<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchDataSharingService
{
    public const CLIENTS = 'clients';
    public const MAIDS = 'maids';
    public const EXTERNAL_OFFICES = 'external_offices';

    private const ENTITIES = [
        self::CLIENTS,
        self::MAIDS,
        self::EXTERNAL_OFFICES,
    ];

    /** @var array<string, array<int, int>> */
    private static array $sourceBranchCache = [];

    public static function entities(): array
    {
        return self::ENTITIES;
    }

    /**
     * Source branches whose records are shared with every other branch.
     *
     * @return array<int, int>
     */
    public function sharedSourceBranchIds(string $entityType): array
    {
        if (! in_array($entityType, self::ENTITIES, true)) {
            return [];
        }

        $cacheKey = $entityType;

        if (array_key_exists($cacheKey, self::$sourceBranchCache)) {
            return self::$sourceBranchCache[$cacheKey];
        }

        if (! Schema::hasTable('branch_data_sharing_policies')) {
            return self::$sourceBranchCache[$cacheKey] = [];
        }

        return self::$sourceBranchCache[$cacheKey] = DB::table('branch_data_sharing_policies')
            ->where('entity_type', $entityType)
            ->where('access_level', 'view')
            ->pluck('branch_id')
            ->map(fn ($branchId) => (int) $branchId)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, array<string, bool>> */
    public function policies(): array
    {
        $policies = [];

        if (! Schema::hasTable('branch_data_sharing_policies')) {
            return $policies;
        }

        DB::table('branch_data_sharing_policies')
            ->whereIn('entity_type', self::ENTITIES)
            ->where('access_level', 'view')
            ->get(['branch_id', 'entity_type'])
            ->each(function ($policy) use (&$policies): void {
                $policies[(int) $policy->branch_id][$policy->entity_type] = true;
            });

        return $policies;
    }

    /**
     * Replace all source-branch sharing policies. Missing checkboxes mean no
     * sharing, so stale policies are removed in the same transaction.
     */
    public function sync(array $submittedPolicies): void
    {
        if (! Schema::hasTable('branch_data_sharing_policies')) {
            return;
        }

        $validBranchIds = DB::table('branches')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validBranchLookup = array_fill_keys($validBranchIds, true);
        $now = now();
        $rows = [];

        foreach ($submittedPolicies as $branchId => $entities) {
            $branchId = (int) $branchId;
            if (! isset($validBranchLookup[$branchId])) {
                continue;
            }

            foreach ((array) $entities as $entityType => $enabled) {
                if (! $enabled || ! in_array($entityType, self::ENTITIES, true)) {
                    continue;
                }

                $rows[] = [
                    'branch_id' => $branchId,
                    'entity_type' => $entityType,
                    'access_level' => 'view',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($rows): void {
            DB::table('branch_data_sharing_policies')->whereIn('entity_type', self::ENTITIES)->delete();

            if ($rows !== []) {
                DB::table('branch_data_sharing_policies')->insert($rows);
            }
        });

        self::flush();
    }

    public static function flush(): void
    {
        self::$sourceBranchCache = [];
    }
}
