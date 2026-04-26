<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $permissionName = 'finance.constraints-approve';

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();

        $attributes = [
            'group_name' => 'finance',
            'section_name' => 'modules',
            'display_name' => 'اعتماد القيود المحاسبية',
            'description' => 'إمكانية اعتماد القيود المحاسبية المنتظرة.',
            'updated_at' => $now,
        ];

        $query = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'admin');

        if ($query->exists()) {
            $query->update($attributes);
            return;
        }

        DB::table('permissions')->insert(array_merge($attributes, [
            'name' => $this->permissionName,
            'guard_name' => 'admin',
            'created_at' => $now,
        ]));
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'admin')
            ->delete();
    }
};
