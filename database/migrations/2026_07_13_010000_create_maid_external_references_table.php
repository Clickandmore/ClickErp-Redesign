<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maid_external_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maid_id')->constrained('maids')->cascadeOnDelete();
            $table->string('source', 50);
            $table->string('external_id', 100);
            $table->timestamps();
            $table->unique(['source', 'external_id'], 'maid_external_refs_source_id_unique');
            $table->unique(['maid_id', 'source', 'external_id'], 'maid_external_refs_maid_source_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maid_external_references');
    }
};
