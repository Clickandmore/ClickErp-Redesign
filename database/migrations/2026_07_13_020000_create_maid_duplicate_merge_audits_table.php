<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maid_duplicate_merge_audits', function (Blueprint $table) {
            $table->id();
            $table->string('passport_key', 150)->index();
            $table->unsignedBigInteger('target_maid_id')->index();
            $table->unsignedBigInteger('deleted_maid_id')->unique();
            $table->unsignedBigInteger('arabia_worker_number')->nullable()->index();
            $table->json('deleted_snapshot');
            $table->json('moved_relationships')->nullable();
            $table->timestamp('merged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maid_duplicate_merge_audits');
    }
};
