<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'user_firebase_tokens_user_id_phone_token_unique';

    /**
     * كان upsert التوكن معطّلاً بسبب خطأ في اسم الحقل، فكان كل تسجيل دخول يُدرج
     * صفاً جديداً. هنا نحذف المكرر الموجود ثم نمنع تكراره على مستوى قاعدة البيانات.
     */
    public function up()
    {
        if (!Schema::hasTable('user_firebase_tokens')) {
            return;
        }

        $this->removeDuplicates();

        if (!$this->indexExists()) {
            Schema::table('user_firebase_tokens', function (Blueprint $table) {
                $table->unique(['user_id', 'phone_token'], self::INDEX);
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('user_firebase_tokens') && $this->indexExists()) {
            Schema::table('user_firebase_tokens', function (Blueprint $table) {
                $table->dropUnique(self::INDEX);
            });
        }
    }

    /**
     * نُبقي على أحدث صف لكل (user_id, phone_token) ونحذف ما عداه.
     */
    private function removeDuplicates(): void
    {
        $duplicateIds = DB::table('user_firebase_tokens as t')
            ->select('t.id')
            ->join(
                DB::raw('(SELECT user_id, phone_token, MAX(id) AS keep_id
                          FROM user_firebase_tokens
                          GROUP BY user_id, phone_token
                          HAVING COUNT(*) > 1) as d'),
                function ($join) {
                    $join->on('t.user_id', '=', 'd.user_id')
                        ->on('t.phone_token', '=', 'd.phone_token');
                }
            )
            ->whereColumn('t.id', '!=', 'd.keep_id')
            ->pluck('t.id');

        foreach ($duplicateIds->chunk(500) as $chunk) {
            DB::table('user_firebase_tokens')->whereIn('id', $chunk->all())->delete();
        }
    }

    private function indexExists(): bool
    {
        try {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes('user_firebase_tokens');

            // Doctrine يُرجع أسماء الفهارس بحروف صغيرة.
            return array_key_exists(strtolower(self::INDEX), array_change_key_case($indexes, CASE_LOWER));
        } catch (\Throwable $e) {
            return false;
        }
    }
};
