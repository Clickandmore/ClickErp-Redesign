<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_attachments')) {
            return;
        }

        Schema::create('sales_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('fn_sales')->cascadeOnDelete();
            $table->string('file')->default('no image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_attachments');
    }
};
