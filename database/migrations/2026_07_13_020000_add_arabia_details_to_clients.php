<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'full_name_en' => fn () => $table->string('full_name_en')->nullable(),
                'identity_date' => fn () => $table->string('identity_date', 50)->nullable(),
                'identity_type' => fn () => $table->string('identity_type', 100)->nullable(),
                'workplace' => fn () => $table->string('workplace')->nullable(),
                'po_box' => fn () => $table->string('po_box', 50)->nullable(),
                'postal_code' => fn () => $table->string('postal_code', 50)->nullable(),
                'notes' => fn () => $table->text('notes')->nullable(),
            ] as $column => $definition) {
                if (!Schema::hasColumn('users', $column)) {
                    $definition();
                }
            }
        });

        if (!Schema::hasTable('client_import_details')) {
            Schema::create('client_import_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('source', 50)->default('arabia');
                $table->string('field_key', 100);
                $table->string('field_label');
                $table->text('field_value')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'source', 'field_key'], 'client_import_detail_unique');
                $table->index(['user_id', 'source', 'sort_order'], 'client_import_detail_order');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_import_details');

        Schema::table('users', function (Blueprint $table) {
            foreach (['full_name_en', 'identity_date', 'identity_type', 'workplace', 'po_box', 'postal_code', 'notes'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
