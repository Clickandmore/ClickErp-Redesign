<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إظهار/إخفاء سكاشن الصفحة الرئيسية في المواقع الخارجية.
 *
 * صف لكل مفتاح قسم مُعرّف في config/home_sections.php. غياب الصف يعني «ظاهر»،
 * لذلك لا نحتاج بذر بيانات ولا يتغير شكل أي موقع بعد تشغيل الـ migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('home_section_settings')) {
            return;
        }

        Schema::create('home_section_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_section_settings');
    }
};
