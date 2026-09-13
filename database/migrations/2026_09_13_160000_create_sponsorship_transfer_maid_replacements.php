<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        // MySQL creates the table before adding foreign keys. If a previous run
        // failed on a long constraint name, safely remove that empty partial table.
        if (Schema::hasTable('sponsorship_transfer_maid_replacements')) {
            if (DB::table('sponsorship_transfer_maid_replacements')->exists()) {
                throw new RuntimeException(
                    'The sponsorship transfer maid replacements table already contains data and cannot be rebuilt automatically.'
                );
            }

            Schema::drop('sponsorship_transfer_maid_replacements');
        }

        Schema::create('sponsorship_transfer_maid_replacements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sponsorship_transfer_id');
            $table->unsignedBigInteger('old_contract_id');
            $table->unsignedBigInteger('new_contract_id');
            $table->unsignedBigInteger('old_maid_id');
            $table->unsignedBigInteger('new_maid_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->boolean('old_maid_made_available')->default(false);
            $table->timestamps();

            $table->foreign('sponsorship_transfer_id', 'stmr_transfer_fk')
                ->references('id')->on('sponsorship_transfers')->cascadeOnDelete();
            $table->foreign('old_contract_id', 'stmr_old_contract_fk')->references('id')->on('contracts');
            $table->foreign('new_contract_id', 'stmr_new_contract_fk')->references('id')->on('contracts');
            $table->foreign('old_maid_id', 'stmr_old_maid_fk')->references('id')->on('maids');
            $table->foreign('new_maid_id', 'stmr_new_maid_fk')->references('id')->on('maids');
            $table->foreign('changed_by', 'stmr_changed_by_fk')
                ->references('id')->on('admins')->nullOnDelete();
        });

        $attributes = ['display_name' => 'تغيير العاملة في فترة تجربة نقل الكفالة', 'group_name' => 'sponsor_transfer', 'section_name' => 'organization_management', 'is_active' => true];
        $attributes = array_filter($attributes, fn ($value, $column) => Schema::hasColumn('permissions', $column), ARRAY_FILTER_USE_BOTH);
        DB::table('permissions')->updateOrInsert(['name' => 'sponsor_transfer_replace_maid', 'guard_name' => 'admin'], $attributes);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_transfer_maid_replacements');
        DB::table('permissions')->where(['name' => 'sponsor_transfer_replace_maid', 'guard_name' => 'admin'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
