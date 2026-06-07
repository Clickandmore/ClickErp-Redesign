<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleBranchPermissionsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('role_branch_permission_scopes')) {
            Schema::create('role_branch_permission_scopes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('branch_id');
                $table->timestamps();

                $table->unique(['role_id', 'branch_id'], 'role_branch_permission_scopes_unique');

                $table->foreign('role_id', 'role_branch_permission_scopes_role_id_foreign')
                    ->references('id')
                    ->on('roles')
                    ->cascadeOnDelete();

                $table->foreign('branch_id', 'role_branch_permission_scopes_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('role_branch_permissions')) {
            return;
        }

        Schema::create('role_branch_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->unique(['role_id', 'branch_id', 'permission_id'], 'role_branch_permissions_unique');
            $table->index(['role_id', 'branch_id'], 'role_branch_permissions_role_branch_index');

            $table->foreign('role_id', 'role_branch_permissions_role_id_foreign')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();

            $table->foreign('branch_id', 'role_branch_permissions_branch_id_foreign')
                ->references('id')
                ->on('branches')
                ->cascadeOnDelete();

            $table->foreign('permission_id', 'role_branch_permissions_permission_id_foreign')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('role_branch_permissions');
        Schema::dropIfExists('role_branch_permission_scopes');
    }
}
