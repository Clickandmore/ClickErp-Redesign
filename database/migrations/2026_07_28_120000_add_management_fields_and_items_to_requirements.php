<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('requirements', 'sort_order')) {
            Schema::table('requirements', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('type')->index();
            });
        }

        if (!Schema::hasColumn('requirements', 'is_active')) {
            Schema::table('requirements', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('sort_order')->index();
            });
        }

        if (!Schema::hasTable('requirement_items')) {
            Schema::create('requirement_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requirement_id')
                    ->constrained('requirements')
                    ->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['requirement_id', 'sort_order']);
            });
        }

        if (!Schema::hasTable('requirement_item_translations')) {
            Schema::create('requirement_item_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requirement_item_id')
                    ->constrained('requirement_items')
                    ->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('text', 500);
                $table->timestamps();
                $table->unique(
                    ['requirement_item_id', 'locale'],
                    'requirement_item_locale_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_item_translations');
        Schema::dropIfExists('requirement_items');

        if (Schema::hasColumn('requirements', 'is_active')) {
            Schema::table('requirements', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('requirements', 'sort_order')) {
            Schema::table('requirements', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
