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
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // msaned, tafwed, taffez, etc.
            $table->string('title'); // عنوان القالب
            $table->text('message'); // نص الرسالة
            $table->boolean('is_active')->default(true);
            $table->json('variables')->nullable(); // المتغيرات المتاحة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
