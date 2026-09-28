<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** ملاحظات نموذج الطلب المخصصة لكل فئة (تُضاف إليها ملاحظات تلقائية من الإعدادات عند العرض). */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hourly_category_notes')) return;
        Schema::create('hourly_category_notes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('service_category_id');
            $t->text('text_ar');
            $t->text('text_en')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
            $t->index(['service_category_id', 'sort_order'], 'hcn_category_order');
            $t->foreign('service_category_id', 'hcn_category_fk')->references('id')->on('hourly_service_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_category_notes');
    }
};
