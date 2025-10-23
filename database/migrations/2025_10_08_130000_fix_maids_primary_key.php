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
        $duplicateSets = DB::table('maids')
            ->select('id', DB::raw('COUNT(*) as total'))
            ->groupBy('id')
            ->having('total', '>', 1)
            ->orderBy('id')
            ->get();

        foreach ($duplicateSets as $duplicate) {
            $excessRows = (int) $duplicate->total - 1;

            for ($i = 0; $i < $excessRows; $i++) {
                // Remove the newest duplicate row while keeping the oldest record for the id.
                DB::statement(
                    'DELETE FROM maids WHERE id = ? ORDER BY updated_at DESC, created_at DESC LIMIT 1',
                    [$duplicate->id]
                );
            }
        }

        $duplicatesRemain = DB::table('maids')
            ->select('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicatesRemain) {
            throw new \RuntimeException(
                'Unable to clean duplicate maid IDs automatically. Resolve duplicates manually before rerunning the migration.'
            );
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
