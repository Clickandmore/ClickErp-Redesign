<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToBranchesTable extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('city')->nullable()->after('manager_name');
            $table->string('district')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('district');
            $table->text('description')->nullable()->after('address');
            $table->json('working_hours')->nullable()->after('longitude');
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp',
                'city',
                'district',
                'postal_code',
                'description',
                'working_hours',
            ]);
        });
    }
}
