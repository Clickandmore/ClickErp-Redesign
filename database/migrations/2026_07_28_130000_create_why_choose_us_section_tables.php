<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('why_choose_us', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('why_choose_us_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('why_choose_us_id')
                ->constrained('why_choose_us')
                ->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description');
            $table->timestamps();
            $table->unique(['why_choose_us_id', 'locale'], 'why_choose_us_locale_unique');
        });

        Schema::create('why_choose_us_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('why_choose_us_id')
                ->constrained('why_choose_us')
                ->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_public_asset')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['why_choose_us_id', 'sort_order'], 'why_choose_us_images_order');
        });

        Schema::create('why_choose_us_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('why_choose_us_id')
                ->constrained('why_choose_us')
                ->cascadeOnDelete();
            $table->string('icon');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['why_choose_us_id', 'sort_order'], 'why_choose_us_features_order');
        });

        Schema::create('why_choose_us_feature_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('why_choose_us_feature_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description');
            $table->timestamps();
            $table->unique(
                ['why_choose_us_feature_id', 'locale'],
                'why_choose_us_feature_locale_unique'
            );
            $table->foreign(
                'why_choose_us_feature_id',
                'why_choose_us_feature_fk'
            )->references('id')
                ->on('why_choose_us_features')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('why_choose_us_feature_translations');
        Schema::dropIfExists('why_choose_us_features');
        Schema::dropIfExists('why_choose_us_images');
        Schema::dropIfExists('why_choose_us_translations');
        Schema::dropIfExists('why_choose_us');
    }
};
