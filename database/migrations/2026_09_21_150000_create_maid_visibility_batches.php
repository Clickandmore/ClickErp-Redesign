<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maid_visibility_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('admin_id')->index();
            $table->json('filters');
            $table->boolean('is_show');
            $table->unsignedInteger('affected_count');
            $table->timestamp('created_at');
            $table->timestamp('undone_at')->nullable();
            $table->unsignedInteger('restored_count')->nullable();
        });
        Schema::create('maid_visibility_batch_items', function (Blueprint $table) {
            $table->uuid('batch_id');
            $table->unsignedBigInteger('maid_id');
            $table->boolean('previous_visibility');
            $table->string('fingerprint', 64);
            $table->primary(['batch_id', 'maid_id']);
        });
        Schema::table('maids', function (Blueprint $table) {
            $table->uuid('visibility_batch_id')->nullable()->index();
        });
        DB::table('permissions')->updateOrInsert(
            ['name' => 'maids_bulk_visibility', 'guard_name' => 'admin'],
            ['display_name' => 'إظهار/إخفاء السير جماعيًا', 'description' => 'إظهار وإخفاء السير حسب الجنسية والمكاتب والحالة، والتراجع عن دفعات الإخفاء.', 'group_name' => 'maids', 'section_name' => 'cv_management', 'is_active' => true]
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('maids', fn (Blueprint $table) => $table->dropColumn('visibility_batch_id'));
        Schema::dropIfExists('maid_visibility_batch_items');
        Schema::dropIfExists('maid_visibility_batches');
    }
};
