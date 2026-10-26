<?php
namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Schema::hasTable/hasColumn hit information_schema on every call; availability checks run them per worker per slot.
 * Only positive answers are remembered, so a table or column added by a later migration is still detected.
 */
class SchemaCache
{
    private static array $known = [];

    public static function hasTable(string $table): bool
    {
        return isset(self::$known[$table]) || (Schema::hasTable($table) && self::$known[$table] = true);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = $table.'.'.$column;
        return isset(self::$known[$key]) || (Schema::hasColumn($table, $column) && self::$known[$key] = true);
    }
}
