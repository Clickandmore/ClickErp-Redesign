<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixMaidsPrimaryKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('UPDATE maids SET id = TRIM(id)');

        $maxId = (int) DB::table('maids')
            ->select(DB::raw('MAX(CAST(id AS UNSIGNED)) as max_id'))
            ->value('max_id');

        $maxId = $maxId > 0 ? $maxId : 0;

        $nonNumericSets = DB::table('maids')
            ->select('id', DB::raw('COUNT(*) as total'))
            ->where(function ($query) {
                $query->whereNull('id')
                    ->orWhere('id', '')
                    ->orWhereRaw("id REGEXP '[^0-9]'");
            })
            ->groupBy('id')
            ->orderBy('id')
            ->get();

        foreach ($nonNumericSets as $row) {
            $occurrences = (int) $row->total;

            for ($i = 0; $i < $occurrences; $i++) {
                $maxId++;

                DB::statement(
                    'UPDATE maids SET id = ? WHERE id <=> ? LIMIT 1',
                    [$maxId, $row->id]
                );
            }
        }

        $duplicateNumericSets = DB::table('maids')
            ->select(DB::raw('CAST(id AS UNSIGNED) AS numeric_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('numeric_id')
            ->having('total', '>', 1)
            ->orderBy('numeric_id')
            ->get();

        foreach ($duplicateNumericSets as $duplicate) {
            $excessRows = (int) $duplicate->total - 1;
            $numericId = (int) $duplicate->numeric_id;

            for ($i = 0; $i < $excessRows; $i++) {
                $maxId++;

                DB::statement(
                    'UPDATE maids SET id = ? WHERE CAST(id AS UNSIGNED) = ? ORDER BY updated_at DESC, created_at DESC LIMIT 1',
                    [$maxId, $numericId]
                );
            }
        }

        $duplicatesRemain = DB::table('maids')
            ->select(DB::raw('CAST(id AS UNSIGNED) AS numeric_id'))
            ->groupBy('numeric_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicatesRemain) {
            throw new \RuntimeException(
                'Unable to normalise maid IDs automatically. Resolve conflicts manually before rerunning the migration.'
            );
        }

        $exactConflicts = DB::table('maids')
            ->select('id', DB::raw('COUNT(*) as total'))
            ->groupBy('id')
            ->having('total', '>', 1)
            ->get();

        if ($exactConflicts->isNotEmpty()) {
            throw new \RuntimeException('Conflicting maid IDs remain (exact match): '.$exactConflicts->toJson());
        }

        $castConflicts = DB::table('maids')
            ->select(
                DB::raw('CAST(id AS UNSIGNED) AS numeric_id'),
                DB::raw('GROUP_CONCAT(id ORDER BY id SEPARATOR \',\') AS conflicting_ids'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('numeric_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($castConflicts->isNotEmpty()) {
            throw new \RuntimeException('Conflicting maid IDs remain after numeric cast: '.$castConflicts->toJson());
        }

        DB::statement('ALTER TABLE `maids` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        $maxId = DB::table('maids')->max('id');
        $nextId = ($maxId === null ? 1 : ((int) $maxId + 1));

        DB::statement('ALTER TABLE `maids` AUTO_INCREMENT = ' . $nextId);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `maids` MODIFY `id` BIGINT UNSIGNED NOT NULL');
    }
}
