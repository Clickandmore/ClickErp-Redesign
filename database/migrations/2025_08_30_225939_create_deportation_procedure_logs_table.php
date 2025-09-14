<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("deportation_procedure_logs", function (Blueprint $table) {
            $table->id();
            $table->foreignId("deportation_procedure_id")->constrained("deportation_procedures")->onDelete("cascade");
            $table->foreignId("admin_id")->constrained("admins")->onDelete("cascade");
            $table->string("action_type");
            $table->string("field_name")->nullable();
            $table->text("old_value")->nullable();
            $table->text("new_value")->nullable();
            $table->text("description");
            $table->json("attachments")->nullable();
            $table->timestamp("created_at")->useCurrent();
            
            $table->index(["deportation_procedure_id", "created_at"], "deportation_logs_procedure_created_idx");
            $table->index("action_type", "deportation_logs_action_idx");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("deportation_procedure_logs");
    }
};
