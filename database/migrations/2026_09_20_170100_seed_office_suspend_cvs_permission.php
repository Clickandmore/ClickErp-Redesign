<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $payload = [
            'group_name' => 'external_agents',
            'section_name' => 'maids_settings',
        ];

        if (Schema::hasColumn('permissions', 'display_name')) {
            $payload['display_name'] = 'تعليق السير الذاتية لوكيل خارجي';
        }

        if (Schema::hasColumn('permissions', 'description')) {
            $payload['description'] = 'إمكانية تعليق أو فك تعليق كل السير الذاتية الخاصة بمكتب خارجي (إخفاؤها عن الموقع والتطبيق).';
        }

        if (Schema::hasColumn('permissions', 'is_active')) {
            $payload['is_active'] = true;
        }

        DB::table('permissions')->updateOrInsert(
            [
                'name' => 'office_suspend_cvs',
                'guard_name' => 'admin',
            ],
            $payload
        );
    }

    public function down(): void
    {
        // تُترك الصلاحية حتى لا تنكسر الأدوار التي مُنحت لها.
    }
};
