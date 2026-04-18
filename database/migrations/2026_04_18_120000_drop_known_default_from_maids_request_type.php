<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('maids', 'request_type')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `maids` MODIFY `request_type` VARCHAR(255) NULL DEFAULT 'custom_spec'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('maids', 'request_type')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `maids` MODIFY `request_type` VARCHAR(255) NULL DEFAULT 'known'");
        }
    }
};
