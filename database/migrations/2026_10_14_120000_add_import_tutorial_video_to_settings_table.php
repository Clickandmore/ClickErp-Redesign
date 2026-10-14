<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فيديو توضيحي لشاشة استيراد البيانات (reports/import) يُعرض من Google Drive.
 * يُتحكم في إظهاره ورابطه من إعدادات لوحة التحكم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'import_tutorial_video_enabled')) {
                $table->boolean('import_tutorial_video_enabled')->default(0);
            }
            if (!Schema::hasColumn('settings', 'import_tutorial_video_url')) {
                $table->string('import_tutorial_video_url', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['import_tutorial_video_enabled', 'import_tutorial_video_url']);
        });
    }
};
