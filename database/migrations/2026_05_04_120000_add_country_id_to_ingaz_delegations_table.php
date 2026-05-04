<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingaz_delegations', function (Blueprint $table) {
            if (!Schema::hasColumn('ingaz_delegations', 'country_id')) {
                $table->foreignId('country_id')
                    ->nullable()
                    ->after('nationality_id')
                    ->constrained('countries')
                    ->nullOnDelete();

                $table->index(['country_id', 'profession_id'], 'ingaz_delegations_country_profession_index');
            }
        });

        if (
            !Schema::hasColumn('ingaz_delegations', 'country_id')
            || !Schema::hasTable('countries')
            || !Schema::hasTable('nationalities_translations')
        ) {
            return;
        }

        $normalize = static fn ($value): string => mb_strtolower(trim((string) $value));

        $countryLookup = [];
        DB::table('countries')
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('id')
            ->get()
            ->each(function ($country) use (&$countryLookup, $normalize) {
                foreach ([$country->name_ar, $country->name_en] as $name) {
                    $normalized = $normalize($name);

                    if ($normalized !== '') {
                        $countryLookup[$normalized] = $country->id;
                    }
                }
            });

        if ($countryLookup === []) {
            return;
        }

        $nationalityLookup = [];
        DB::table('nationalities_translations')
            ->select('nationality_id', 'title', 'sub_title')
            ->orderBy('nationality_id')
            ->get()
            ->each(function ($translation) use (&$nationalityLookup, $countryLookup, $normalize) {
                foreach ([$translation->title, $translation->sub_title] as $name) {
                    $normalized = $normalize($name);

                    if ($normalized !== '' && isset($countryLookup[$normalized])) {
                        $nationalityLookup[$translation->nationality_id] = $countryLookup[$normalized];
                        break;
                    }
                }
            });

        if ($nationalityLookup === []) {
            return;
        }

        DB::table('ingaz_delegations')
            ->whereNull('country_id')
            ->select('id', 'nationality_id')
            ->orderBy('id')
            ->chunkById(200, function ($delegations) use ($nationalityLookup) {
                foreach ($delegations as $delegation) {
                    $countryId = $nationalityLookup[$delegation->nationality_id] ?? null;

                    if ($countryId) {
                        DB::table('ingaz_delegations')
                            ->where('id', $delegation->id)
                            ->update(['country_id' => $countryId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('ingaz_delegations', function (Blueprint $table) {
            if (Schema::hasColumn('ingaz_delegations', 'country_id')) {
                $table->dropForeign(['country_id']);
                $table->dropIndex('ingaz_delegations_country_profession_index');
                $table->dropColumn('country_id');
            }
        });
    }
};
