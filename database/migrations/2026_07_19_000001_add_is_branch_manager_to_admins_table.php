<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BranchController (assignManager / branchAdmins / removeAdmin), the Admin model cast
 * and resources/views/admin/branches/index.blade.php all read admins.is_branch_manager,
 * but no migration ever created it — every one of those endpoints raised a 500.
 */
class AddIsBranchManagerToAdminsTable extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('admins', 'is_branch_manager')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->boolean('is_branch_manager')->default(false)->after('branch_id');
        });
    }

    public function down()
    {
        if (! Schema::hasColumn('admins', 'is_branch_manager')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('is_branch_manager');
        });
    }
}
