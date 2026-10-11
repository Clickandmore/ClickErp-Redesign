<?php

namespace App\Support;

/** Keep new imported master records in the uploading branch when a central reviewer approves them. */
final class DataImportBranchContext
{
    public static bool $active = false;
    public static ?int $branchId = null;

    public static function run(?int $branchId, callable $callback)
    {
        $previous = [self::$active, self::$branchId];
        self::$active = true;
        self::$branchId = $branchId;
        try { return $callback(); }
        finally { [self::$active, self::$branchId] = $previous; }
    }
}
