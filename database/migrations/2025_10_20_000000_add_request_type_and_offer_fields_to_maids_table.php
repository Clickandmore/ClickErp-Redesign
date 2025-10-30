<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            if (!Schema::hasColumn('maids', 'request_type')) {
                $table->string('request_type')->nullable()->default('known')->after('status');
            }

            if (!Schema::hasColumn('maids', 'offer_price')) {
                $table->decimal('offer_price', 10, 2)->nullable()->after('price');
            }

            if (!Schema::hasColumn('maids', 'offer_duration')) {
                $table->string('offer_duration')->nullable()->after('offer_price');
            }

            if (!Schema::hasColumn('maids', 'offer_description')) {
                $table->text('offer_description')->nullable()->after('offer_duration');
            }

            if (!Schema::hasColumn('maids', 'offer_starts_at')) {
                $table->date('offer_starts_at')->nullable()->after('offer_description');
            }

            if (!Schema::hasColumn('maids', 'offer_expires_at')) {
                $table->date('offer_expires_at')->nullable()->after('offer_starts_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            if (Schema::hasColumn('maids', 'offer_expires_at')) {
                $table->dropColumn('offer_expires_at');
            }

            if (Schema::hasColumn('maids', 'offer_starts_at')) {
                $table->dropColumn('offer_starts_at');
            }

            if (Schema::hasColumn('maids', 'offer_description')) {
                $table->dropColumn('offer_description');
            }

            if (Schema::hasColumn('maids', 'offer_duration')) {
                $table->dropColumn('offer_duration');
            }

            if (Schema::hasColumn('maids', 'offer_price')) {
                $table->dropColumn('offer_price');
            }

            if (Schema::hasColumn('maids', 'request_type')) {
                $table->dropColumn('request_type');
            }
        });
    }
};
