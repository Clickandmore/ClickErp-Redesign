<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ingaz_delegations MODIFY delegation_date_hijri VARCHAR(50) NULL');
        DB::statement('ALTER TABLE ingaz_delegations MODIFY profession_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE ingaz_delegations MODIFY worker_name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE ingaz_delegations MODIFY worker_passport_number VARCHAR(100) NULL');
        DB::statement('ALTER TABLE ingaz_delegations MODIFY external_office_name VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE ingaz_delegations SET delegation_date_hijri = '' WHERE delegation_date_hijri IS NULL");
        DB::statement("UPDATE ingaz_delegations SET worker_name = '' WHERE worker_name IS NULL");
        DB::statement("UPDATE ingaz_delegations SET worker_passport_number = '' WHERE worker_passport_number IS NULL");
        DB::statement("UPDATE ingaz_delegations SET external_office_name = '' WHERE external_office_name IS NULL");

        DB::statement('ALTER TABLE ingaz_delegations MODIFY delegation_date_hijri VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE ingaz_delegations MODIFY worker_name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE ingaz_delegations MODIFY worker_passport_number VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE ingaz_delegations MODIFY external_office_name VARCHAR(255) NOT NULL');
    }
};
