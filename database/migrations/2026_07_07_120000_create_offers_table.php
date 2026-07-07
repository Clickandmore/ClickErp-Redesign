<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOffersTable extends Migration
{
    public function up()
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->default('general')->index();
            $table->string('title');
            $table->text('description');
            $table->string('image');
            $table->decimal('price_before_discount', 12, 2)->nullable();
            $table->decimal('price_after_discount', 12, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->string('url')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('offer_nationality', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('nationality_id')->constrained('nationalities')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['offer_id', 'nationality_id']);
        });

        Schema::create('maid_offer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('maid_id')->constrained('maids')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['offer_id', 'maid_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('maid_offer');
        Schema::dropIfExists('offer_nationality');
        Schema::dropIfExists('offers');
    }
}
