<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'status')) {
                $table->string('status')->default('active')->after('admin_type');
            }
            if (! Schema::hasColumn('admins', 'note')) {
                $table->text('note')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('admins', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
