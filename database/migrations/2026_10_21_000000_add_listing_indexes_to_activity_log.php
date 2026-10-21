<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * صفحة سجل النشاط ترتب دائماً حسب created_at وتفلتر بالتاريخ ونوع العملية؛
 * بدون هذه الفهارس يقوم MySQL بفرز الجدول كاملاً في كل تحميل للصفحة.
 */
return new class extends Migration
{
    private function table(): string
    {
        return config('activitylog.table_name', 'activity_log');
    }

    private function hasIndex(string $name): bool
    {
        return DB::select('SHOW INDEX FROM `' . $this->table() . '` WHERE Key_name = ?', [$name]) !== [];
    }

    public function up(): void
    {
        if (! Schema::hasTable($this->table())) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table) {
            if (! $this->hasIndex('activity_log_created_at_id_index')) {
                $table->index(['created_at', 'id'], 'activity_log_created_at_id_index');
            }
            if (Schema::hasColumn($this->table(), 'event') && ! $this->hasIndex('activity_log_event_index')) {
                $table->index('event', 'activity_log_event_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable($this->table())) {
            return;
        }

        Schema::table($this->table(), function (Blueprint $table) {
            if ($this->hasIndex('activity_log_created_at_id_index')) {
                $table->dropIndex('activity_log_created_at_id_index');
            }
            if ($this->hasIndex('activity_log_event_index')) {
                $table->dropIndex('activity_log_event_index');
            }
        });
    }
};
