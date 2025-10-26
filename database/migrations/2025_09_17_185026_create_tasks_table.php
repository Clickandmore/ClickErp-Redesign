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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان المهمة
            $table->text('description'); // وصف/موضوع المهمة
            $table->unsignedBigInteger('assigned_by')->nullable(); // المستخدم الذي أنشأ المهمة
            $table->unsignedBigInteger('assigned_to')->nullable(); // المستخدم المكلف بالمهمة
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium'); // مستوى الأهمية
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending'); // حالة المهمة
            $table->date('due_date')->nullable(); // تاريخ الاستحقاق
            $table->date('completed_date')->nullable(); // تاريخ الإنجاز
            $table->json('attachments')->nullable(); // المرفقات
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->text('completion_notes')->nullable(); // ملاحظات عند الإنجاز
            $table->timestamps();

            // Foreign keys
            $table->foreign('assigned_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('admins')->onDelete('set null');

            // Indexes for better performance
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
