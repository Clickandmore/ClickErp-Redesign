<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('translation_terms')) {
            return;
        }

        Schema::create('translation_terms', function (Blueprint $table) {
            $table->id();
            $table->string('group', 120);
            $table->text('key');
            $table->string('key_hash', 64);
            $table->string('locale', 20);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key_hash', 'locale'], 'translation_terms_unique');
            $table->index(['group', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_terms');
    }
};
