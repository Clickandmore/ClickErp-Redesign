<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * فئات الخدمة: طبقة بين الإعدادات العامة وعناصر الخدمة.
 * يُنقل كل نص category قائم في hourly_packages إلى فئة، ويبقى العمود النصي
 * كاسم مرجعي متزامن حتى لا ينكسر أي كود أو تقرير قديم يقرؤه.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hourly_service_categories')) {
            Schema::create('hourly_service_categories', function (Blueprint $t) {
                $t->id();
                $t->string('name_ar', 150);
                $t->string('name_en', 150)->nullable();
                $t->string('slug', 160)->unique();
                $t->text('description_ar')->nullable();
                $t->text('description_en')->nullable();
                $t->string('icon_type', 10)->default('library'); // library | upload
                $t->string('icon')->nullable();
                $t->string('color', 20)->nullable();
                $t->string('image')->nullable();
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('sort_order')->default(0);
                $t->timestamps();
                $t->softDeletes();
            });
        }

        // سجل تعديلات الفئة بنفس أسلوب hourly_package_logs.
        if (!Schema::hasTable('hourly_service_category_logs')) {
            Schema::create('hourly_service_category_logs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('service_category_id')->index();
                $t->string('category_name');
                $t->string('section', 30);
                $t->string('type', 30);
                $t->json('before_data')->nullable();
                $t->json('after_data')->nullable();
                $t->unsignedBigInteger('admin_id')->nullable();
                $t->string('admin_name')->nullable();
                $t->timestamps();
            });
        }

        if (Schema::hasTable('hourly_packages') && !Schema::hasColumn('hourly_packages', 'service_category_id')) {
            Schema::table('hourly_packages', function (Blueprint $t) {
                $t->unsignedBigInteger('service_category_id')->nullable()->after('code')->index();
            });
        }

        if (!Schema::hasTable('hourly_packages')) return;

        // نقل القيم النصية الحالية إلى فئات (مرة واحدة وبشكل آمن لإعادة التشغيل).
        $names = DB::table('hourly_packages')->whereNull('service_category_id')->whereNotNull('category')->distinct()->pluck('category');
        $order = (int) DB::table('hourly_service_categories')->max('sort_order');
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $id = DB::table('hourly_service_categories')->whereNull('deleted_at')->where('name_ar', $name)->value('id');
            if (!$id) {
                $base = Str::slug($name) ?: 'category';
                $slug = $base;
                for ($i = 2; DB::table('hourly_service_categories')->where('slug', $slug)->exists(); $i++) $slug = $base.'-'.$i;
                $id = DB::table('hourly_service_categories')->insertGetId([
                    'name_ar' => $name, 'name_en' => $name, 'slug' => $slug, 'icon_type' => 'library', 'icon' => 'fa-broom',
                    'is_active' => true, 'sort_order' => ++$order, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('hourly_packages')->whereNull('service_category_id')->where('category', $name)->update(['service_category_id' => $id]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hourly_packages') && Schema::hasColumn('hourly_packages', 'service_category_id')) {
            Schema::table('hourly_packages', function (Blueprint $t) {
                $t->dropIndex(['service_category_id']);
                $t->dropColumn('service_category_id');
            });
        }
        Schema::dropIfExists('hourly_service_category_logs');
        Schema::dropIfExists('hourly_service_categories');
    }
};
