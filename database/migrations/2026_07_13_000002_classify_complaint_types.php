<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaint_types', function (Blueprint $table) {
            if (!Schema::hasColumn('complaint_types', 'complainant_type')) {
                $table->string('complainant_type')->nullable()->after('name');
            }
            if (!Schema::hasColumn('complaint_types', 'against_type')) {
                $table->string('against_type')->nullable()->after('complainant_type');
            }
            if (!Schema::hasColumn('complaint_types', 'description')) {
                $table->text('description')->nullable()->after('against_type');
            }
            if (!Schema::hasColumn('complaint_types', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
            if (!Schema::hasColumn('complaint_types', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
        });

        $now = now();
        $types = [
            ['name' => 'شكوى عميل ضد عاملة', 'complainant_type' => 'client', 'against_type' => 'worker', 'sort_order' => 10],
            ['name' => 'شكوى عاملة ضد عميل', 'complainant_type' => 'worker', 'against_type' => 'client', 'sort_order' => 20],
            ['name' => 'شكوى عميل ضد المكتب', 'complainant_type' => 'client', 'against_type' => 'internal_office', 'sort_order' => 30],
            ['name' => 'شكوى المكتب ضد عميل', 'complainant_type' => 'internal_office', 'against_type' => 'client', 'sort_order' => 40],
            ['name' => 'شكوى مكتب خارجي ضد المكتب', 'complainant_type' => 'external_office', 'against_type' => 'internal_office', 'sort_order' => 50],
            ['name' => 'شكوى المكتب ضد مكتب خارجي', 'complainant_type' => 'internal_office', 'against_type' => 'external_office', 'sort_order' => 60],
            ['name' => 'شكوى عامة', 'complainant_type' => 'other', 'against_type' => 'other', 'sort_order' => 100],
        ];

        foreach ($types as $type) {
            DB::table('complaint_types')->updateOrInsert(
                ['name' => $type['name']],
                $type + ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::table('complaint_types', function (Blueprint $table) {
            foreach (['sort_order', 'is_active', 'description', 'against_type', 'complainant_type'] as $column) {
                if (Schema::hasColumn('complaint_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
