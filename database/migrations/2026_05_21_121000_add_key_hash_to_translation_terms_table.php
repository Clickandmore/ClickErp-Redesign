<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('translation_terms')) {
            return;
        }

        if (!Schema::hasColumn('translation_terms', 'key_hash')) {
            Schema::table('translation_terms', function (Blueprint $table) {
                $table->string('key_hash', 64)->nullable()->after('key');
            });
        }

        DB::table('translation_terms')
            ->select(['id', 'key'])
            ->orderBy('id')
            ->chunkById(200, function ($terms) {
                foreach ($terms as $term) {
                    DB::table('translation_terms')
                        ->where('id', $term->id)
                        ->update(['key_hash' => sha1((string) $term->key)]);
                }
            });

        if (!$this->indexExists('translation_terms_unique')) {
            Schema::table('translation_terms', function (Blueprint $table) {
                $table->unique(['group', 'key_hash', 'locale'], 'translation_terms_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('translation_terms') || !Schema::hasColumn('translation_terms', 'key_hash')) {
            return;
        }

        $hasUniqueIndex = $this->indexExists('translation_terms_unique');

        Schema::table('translation_terms', function (Blueprint $table) use ($hasUniqueIndex) {
            if ($hasUniqueIndex) {
                $table->dropUnique('translation_terms_unique');
            }
            $table->dropColumn('key_hash');
        });
    }

    private function indexExists(string $indexName): bool
    {
        $indexes = DB::select('SHOW INDEX FROM translation_terms WHERE Key_name = ?', [$indexName]);

        return count($indexes) > 0;
    }
};
