<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** إغلاقات مؤقتة للفئة (الكل أو أحياء محددة)؛ تُستثنى من التوفر إلا للعناصر التي تتجاهل أيام الإغلاق. */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hourly_category_closures')) return;
        Schema::create('hourly_category_closures', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('service_category_id');
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->json('district_ids')->nullable(); // null = كل الأحياء
            $t->string('reason', 500)->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index(['service_category_id', 'starts_at', 'ends_at'], 'hcc_category_period');
            $t->foreign('service_category_id', 'hcc_category_fk')->references('id')->on('hourly_service_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_category_closures');
    }
};
