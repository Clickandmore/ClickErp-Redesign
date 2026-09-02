<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الأسئلة الشائعة الخاصة بكل مقال في المدونة.
 *
 * سؤال/جواب مستقل عن صفحة "الأسئلة الشائعة" العامة، لأن أسئلة المقال جزء من
 * محتواه وتُرسل للموقع الخارجي داخل نفس رد /client/blogs/{slug} حتى يبني منها
 * سكشن الأكورديون ووسم FAQPage في نتائج البحث.
 *
 * النصوص في جدول ترجمات منفصل مثل blog_translations حتى يكون لكل سؤال نسخة
 * عربية وأخرى إنجليزية.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blog_faqs')) {
            Schema::create('blog_faqs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_id')->constrained('blogs')->cascadeOnDelete();
                // ترتيب ظهور السؤال داخل المقال، يحدده المحرر بترتيب الصفوف في النموذج.
                $table->integer('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('blog_faq_translations')) {
            Schema::create('blog_faq_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_faq_id')->constrained('blog_faqs')->cascadeOnDelete();
                $table->string('locale', 10)->index();
                $table->string('question', 500);
                $table->text('answer')->nullable();
                $table->timestamps();

                $table->unique(['blog_faq_id', 'locale']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_faq_translations');
        Schema::dropIfExists('blog_faqs');
    }
};
