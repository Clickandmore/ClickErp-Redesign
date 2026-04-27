<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maid_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maid_id')->constrained('maids')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['maid_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maid_attachments');
    }
};
