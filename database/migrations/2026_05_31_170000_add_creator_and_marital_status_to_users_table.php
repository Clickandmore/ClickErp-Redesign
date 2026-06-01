<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatorAndMaritalStatusToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('is_blocked');
                $table->foreign('created_by')
                    ->references('id')
                    ->on('admins')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'marital_status')) {
                $table->string('marital_status', 20)->nullable()->after('birthdate');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('users', 'marital_status')) {
                $table->dropColumn('marital_status');
            }
        });
    }
}
