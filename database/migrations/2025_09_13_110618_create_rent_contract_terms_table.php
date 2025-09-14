<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rent_contract_terms', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('عنوان البند');
            $table->text('content')->comment('محتوى البند');
            $table->integer('order')->default(0)->comment('ترتيب البند');
            $table->boolean('is_active')->default(true)->comment('هل البند نشط');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_contract_terms');
    }
};
