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
        Schema::create('fn_purchases', function (Blueprint $table) {
            $table->id();
            $table->integer('code')->comment('رقم الفاتورة');
            $table->foreignId('supplier_id')
                ->comment('المورد')
                ->references('id')
                ->on('fn_suppliers')
                ->onDelete('cascade');
            $table->foreignId('credit_supplier_id')
                ->comment('حساب المورد في جدول الحسابات')
                ->constrained('fn_accounts')
                ->onDelete('cascade');
            $table->date('date');
            $table->string('notes',1000)->default('لا يوجد');
            $table->double('total_item')
                ->comment('مجموع السعر * الكمية قبل الخصومات والضرائب');
            $table->double('tax_per')->default(0);
            $table->double('tax_value')->default(0);
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->double('discount')->default(0);
            $table->double('final_total')->default(0);
            $table->boolean('is_confirmed')->default(0);
            $table->unsignedBigInteger('constraint_id')->nullable()->comment('بيتحفظ لما اعمل تأكيد للفاتورة');
            $table->string('type');
            $table->string('payment_method')->default('deferred');
            $table->string('file')->default('no_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
