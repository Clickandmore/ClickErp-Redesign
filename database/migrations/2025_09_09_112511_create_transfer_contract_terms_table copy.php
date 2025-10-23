<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('transfer_contract_terms')) {
            return;
        }

        Schema::create('transfer_contract_terms', function (Blueprint $table) {
            $table->id();
            $table->string('section_title'); // عنوان القسم مثل "أولاً: موضوع العقد"
            $table->integer('section_order')->default(1); // ترتيب القسم
            $table->integer('term_number')->nullable(); // رقم البند
            $table->text('term_content'); // محتوى البند
            $table->integer('term_order')->default(1); // ترتيب البند داخل القسم
            $table->integer('page_number')->default(1); // رقم الصفحة (1, 2, 3)
            $table->boolean('is_active')->default(true); // تفعيل/تعطيل البند
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_contract_terms');
    }
};
