<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('sms_templates', function (Blueprint $table) {
        $table->json('clickchat_binding')->nullable();
    }); }
    public function down(): void { Schema::table('sms_templates', fn (Blueprint $table) => $table->dropColumn('clickchat_binding')); }
};
