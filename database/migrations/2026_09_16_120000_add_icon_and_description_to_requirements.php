<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أيقونة لكل تبويب ولكل خطوة، ووصف مترجم للخطوة — يستهلكها قسم "متطلبات الاستقدام"
 * في الموقع الخارجي بدل النصوص الثابتة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('requirements', 'icon')) {
            Schema::table('requirements', function (Blueprint $table) {
                $table->string('icon', 100)->nullable()->after('type');
            });
        }

        if (!Schema::hasColumn('requirement_items', 'icon')) {
            Schema::table('requirement_items', function (Blueprint $table) {
                $table->string('icon', 100)->nullable()->after('requirement_id');
            });
        }

        if (!Schema::hasColumn('requirement_item_translations', 'description')) {
            Schema::table('requirement_item_translations', function (Blueprint $table) {
                $table->text('description')->nullable()->after('text');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('requirement_item_translations', 'description')) {
            Schema::table('requirement_item_translations', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasColumn('requirement_items', 'icon')) {
            Schema::table('requirement_items', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }

        if (Schema::hasColumn('requirements', 'icon')) {
            Schema::table('requirements', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
