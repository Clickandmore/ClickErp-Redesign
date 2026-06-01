<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_custodies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employee')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('fn_accounts')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('value', 12, 2)->default(0);
            $table->string('location');
            $table->date('received_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->string('status')->default('active');
            $table->string('file')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_custodies');
    }
};
