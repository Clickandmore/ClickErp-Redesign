<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('sms_templates', 'channel')) {
                $table->string('channel')->default(SmsTemplate::CHANNEL_SMS)->after('title');
            }

            if (!Schema::hasColumn('sms_templates', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('channel');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            if (Schema::hasColumn('sms_templates', 'sort_order')) {
                $table->dropColumn('sort_order');
            }

            if (Schema::hasColumn('sms_templates', 'channel')) {
                $table->dropColumn('channel');
            }
        });
    }
};
