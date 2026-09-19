<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nationalities', function (Blueprint $table) {
            $table->decimal('offer_price', 10, 2)->nullable()->after('price');
            $table->string('offer_duration')->nullable()->after('offer_price');
            $table->text('offer_description')->nullable()->after('offer_duration');
            $table->date('offer_starts_at')->nullable()->after('offer_description');
            $table->date('offer_expires_at')->nullable()->after('offer_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('nationalities', function (Blueprint $table) {
            $table->dropColumn([
                'offer_price',
                'offer_duration',
                'offer_description',
                'offer_starts_at',
                'offer_expires_at',
            ]);
        });
    }
};
