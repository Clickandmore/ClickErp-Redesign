<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أرشفة عقود الاستقدام والتأجير: العقد يبقى موجوداً بكل ارتباطاته،
 * وتخفيه الشاشات التشغيلية فقط. لا يُستخدم deleted_at عمداً حتى لا تنقطع
 * علاقات الفواتير والقيود بالعقد.
 */
return new class extends Migration {
    private const TABLES = ['contracts', 'rental_contracts'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'archived_by')) {
                    $table->unsignedBigInteger('archived_by')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'archive_reason')) {
                    $table->string('archive_reason', 500)->nullable();
                }
            });
        }

        if (!Schema::hasTable('contract_archive_logs')) {
            Schema::create('contract_archive_logs', function (Blueprint $table) {
                $table->id();
                $table->string('contract_type', 20);
                $table->unsignedBigInteger('contract_id');
                $table->string('action', 20);
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->timestamps();

                $table->index(['contract_type', 'contract_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_archive_logs');

        foreach (self::TABLES as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'archived_at')) {
                    $table->dropIndex(['archived_at']);
                }
                $columns = array_values(array_filter(
                    ['archived_at', 'archived_by', 'archive_reason'],
                    fn ($column) => Schema::hasColumn($tableName, $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
