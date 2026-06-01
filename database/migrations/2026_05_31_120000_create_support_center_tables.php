<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('video_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('support_article_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('support_knowledge_articles')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('message');
            $table->string('category')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        DB::table('support_knowledge_articles')->insert([
            [
                'title' => 'نظرة عامة على لوحة التحكم',
                'category' => 'البدء',
                'summary' => 'شرح سريع لأقسام لوحة التحكم وكيفية الوصول للعمليات اليومية.',
                'content' => "تبدأ أغلب العمليات من القائمة الجانبية في لوحة التحكم.\n\n1. استخدم الصفحة الرئيسية لمتابعة المؤشرات السريعة مثل الطلبات والعقود والعملاء.\n2. من إدارة العمليات يمكنك الوصول إلى السير الذاتية والطلبات والعقود والإيواء والسفر والشكاوى.\n3. من الإعدادات يمكنك ضبط بيانات الموقع والبيانات المرجعية مثل الجنسيات والمكاتب والمهن.\n4. استخدم البحث داخل هذا المركز للوصول إلى شرح أي عملية بسرعة.",
                'is_published' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'إدارة السير الذاتية',
                'category' => 'العمليات',
                'summary' => 'طريقة إضافة ومتابعة السير الذاتية للعاملات وتصنيف الحالات.',
                'content' => "من القائمة الجانبية افتح إدارة العمليات ثم إدارة السير الذاتية.\n\nيمكنك إضافة سيرة ذاتية جديدة، مراجعة السير المتاحة للاستقدام أو التأجير، متابعة السير غير المتاحة، وإدارة السير المحذوفة حسب الصلاحيات المتاحة لحسابك.\n\nعند إنشاء سيرة ذاتية تأكد من إدخال البيانات الأساسية مثل الجنسية، المهنة، بيانات الجواز، المهارات، والصور أو الملفات المطلوبة.",
                'is_published' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'إدارة الطلبات والعقود',
                'category' => 'العمليات',
                'summary' => 'متابعة طلبات العملاء وربطها بالعقود ومراحل التنفيذ.',
                'content' => "افتح إدارة العمليات ثم الطلبات أو عقود الاستقدام حسب نوع العملية.\n\nفي الطلبات يمكنك مراجعة بيانات العميل، العاملة المطلوبة، حالة الطلب، والمسؤول عن المتابعة. في العقود يمكنك متابعة المراحل مثل مساند، التفويض، التفييز، التذاكر، الوصول، الضمان، والإغلاق.\n\nينصح بتحديث حالة العقد فور انتهاء كل خطوة حتى تظهر المؤشرات والتقارير بشكل صحيح.",
                'is_published' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'فتح تذكرة دعم داخلية',
                'category' => 'الدعم',
                'summary' => 'طريقة إرسال مشكلة أو طلب مساعدة لفريق الدعم.',
                'content' => "من صفحة الدعم استخدم نموذج فتح تذكرة.\n\nاكتب موضوعاً واضحاً، اختر الأولوية المناسبة، ثم اشرح المشكلة أو الطلب بالتفصيل. يفضل ذكر رقم الطلب أو العقد أو اسم العميل عند وجوده حتى يستطيع فريق الدعم مراجعة الحالة بسرعة.",
                'is_published' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('support_article_attachments');
        Schema::dropIfExists('support_knowledge_articles');
    }
};
