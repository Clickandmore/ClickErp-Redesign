<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("deportation_procedures", function (Blueprint $table) {
            if (!Schema::hasColumn("deportation_procedures", "attachments")) {
                $table->json("attachments")->nullable()->after("notes");
            }
        });
    }

    public function down(): void
    {
        Schema::table("deportation_procedures", function (Blueprint $table) {
            $table->dropColumn("attachments");
        });
    }
};
