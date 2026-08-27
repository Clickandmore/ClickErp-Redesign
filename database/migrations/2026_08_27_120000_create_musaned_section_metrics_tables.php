<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('musaned_section_metrics')) {
            Schema::create('musaned_section_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('musaned_section_id')
                    ->constrained('musaned_sections')
                    ->cascadeOnDelete();
                $table->string('type', 20);
                $table->decimal('value', 5, 2)->default(0);
                $table->string('icon', 2048)->nullable();
                $table->boolean('is_visible')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index(['musaned_section_id', 'type', 'is_visible'], 'musaned_metrics_lookup');
            });
        } elseif (!Schema::hasColumn('musaned_section_metrics', 'icon')) {
            Schema::table('musaned_section_metrics', function (Blueprint $table) {
                $table->string('icon', 2048)->nullable()->after('value');
            });
        }

        if (!Schema::hasTable('musaned_section_metric_translations')) {
            Schema::create('musaned_section_metric_translations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('musaned_section_metric_id');
                $table->foreign('musaned_section_metric_id', 'musaned_metric_translation_fk')
                    ->references('id')
                    ->on('musaned_section_metrics')
                    ->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('title');
                $table->timestamps();
                $table->unique(
                    ['musaned_section_metric_id', 'locale'],
                    'musaned_metric_locale_unique'
                );
            });
        }

        $definitions = [
            'ratio' => [
                ['allowed_period_commitment_rate', 'allowed_period_commitment_title'],
                ['workforce_arrival_rate', 'workforce_arrival_title'],
                ['complaint_handling_rate', 'complaint_handling_title'],
                ['arrival_duration_commitment_rate', 'arrival_duration_commitment_title'],
            ],
            'rating' => [
                ['customer_recommendation_rating', 'customer_recommendation_title'],
                ['inquiry_response_rating', 'inquiry_response_title'],
                ['recruitment_duration_rating', 'recruitment_duration_title'],
                ['complaint_response_rating', 'complaint_response_title'],
            ],
        ];

        foreach (DB::table('musaned_sections')->get() as $section) {
            foreach ($definitions as $type => $metrics) {
                foreach ($metrics as $sortOrder => [$valueColumn, $titleColumn]) {
                    $metric = DB::table('musaned_section_metrics')
                        ->where('musaned_section_id', $section->id)
                        ->where('type', $type)
                        ->where('sort_order', $sortOrder)
                        ->first();

                    if ($metric) {
                        $metricId = $metric->id;
                    } else {
                        $metricId = DB::table('musaned_section_metrics')->insertGetId([
                            'musaned_section_id' => $section->id,
                            'type' => $type,
                            'value' => $section->{$valueColumn} ?? 0,
                            'icon' => null,
                            'is_visible' => true,
                            'sort_order' => $sortOrder,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $translations = DB::table('musaned_section_translations')
                        ->where('musaned_section_id', $section->id)
                        ->get();

                    foreach ($translations as $translation) {
                        $title = $translation->{$titleColumn} ?? null;

                        if ($title !== null && trim((string) $title) !== '') {
                            DB::table('musaned_section_metric_translations')->updateOrInsert(
                                [
                                    'musaned_section_metric_id' => $metricId,
                                    'locale' => $translation->locale,
                                ],
                                [
                                'title' => $title,
                                'created_at' => now(),
                                'updated_at' => now(),
                                ]
                            );
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('musaned_section_metric_translations');
        Schema::dropIfExists('musaned_section_metrics');
    }
};
