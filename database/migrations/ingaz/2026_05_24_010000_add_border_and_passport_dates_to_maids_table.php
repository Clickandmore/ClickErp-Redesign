<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBorderAndPassportDatesToMaidsTable extends Migration
{
    public function up()
    {
        Schema::table('maids', function (Blueprint $table) {
            if (!Schema::hasColumn('maids', 'border_number')) {
                $table->string('border_number', 50)->nullable()->after('id_expiry_date');
            }

            if (!Schema::hasColumn('maids', 'passport_issue_date')) {
                $table->date('passport_issue_date')->nullable()->after('border_number');
            }

            if (!Schema::hasColumn('maids', 'passport_expiry_date')) {
                $table->date('passport_expiry_date')->nullable()->after('passport_issue_date');
            }
        });
    }

    public function down()
    {
        Schema::table('maids', function (Blueprint $table) {
            if (Schema::hasColumn('maids', 'passport_expiry_date')) {
                $table->dropColumn('passport_expiry_date');
            }

            if (Schema::hasColumn('maids', 'passport_issue_date')) {
                $table->dropColumn('passport_issue_date');
            }

            if (Schema::hasColumn('maids', 'border_number')) {
                $table->dropColumn('border_number');
            }
        });
    }
}
