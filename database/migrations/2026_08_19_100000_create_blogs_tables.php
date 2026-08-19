<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مدونة الموقع الخارجي: مقالات يضيفها فريق التسويق من لوحة التحكم
 * وتُعرض في صفحة /blog وفي سكشن على الصفحة الرئيسية.
 *
 * النصوص في جدول ترجمات منفصل (astrotomic/laravel-translatable) حتى يكون
 * لكل مقال نسخة عربية وأخرى إنجليزية — وهو شرط عمل وسوم hreflang في الموقع.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blogs')) {
            Schema::create('blogs', function (Blueprint $table) {
                $table->id();
                // يدخل في رابط المقال (/blog/{slug})، لذلك يجب أن يكون فريدًا.
                $table->string('slug')->unique();
                $table->string('image')->nullable();
                $table->string('category')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                // المقالات المميزة هي التي تظهر في سكشن الصفحة الرئيسية.
                $table->boolean('is_featured')->default(false)->index();
                $table->integer('sort_order')->default(0)->index();
                // تاريخ النشر المعروض، ويُستخدم أيضًا لإخفاء المقالات المجدولة مستقبلًا.
                $table->timestamp('published_at')->nullable()->index();
                $table->unsignedBigInteger('views_count')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('blog_translations')) {
            Schema::create('blog_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
                $table->string('locale', 10)->index();
                $table->string('title');
                // ملخص قصير يظهر في البطاقات وفي وصف نتيجة البحث عند غياب meta_description.
                $table->text('excerpt')->nullable();
                $table->longText('content')->nullable();
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 500)->nullable();
                $table->timestamps();

                $table->unique(['blog_id', 'locale']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_translations');
        Schema::dropIfExists('blogs');
    }
};
