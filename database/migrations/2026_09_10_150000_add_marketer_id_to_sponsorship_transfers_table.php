<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * المسوق المسؤول عن طلب نقل الخدمات.
     * العمود nullable في قاعدة البيانات حتى لا تتعطل الطلبات المسجلة سابقًا،
     * بينما الإلزام مطبَّق على مستوى التحقق عند الإنشاء.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sponsorship_transfers')) {
            return;
        }

        if (Schema::hasColumn('sponsorship_transfers', 'marketer_id')) {
            return;
        }

        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('marketer_id')->nullable()->after('new_sponsor_id');
            $table->index('marketer_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sponsorship_transfers') || !Schema::hasColumn('sponsorship_transfers', 'marketer_id')) {
            return;
        }

        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropIndex(['marketer_id']);
            $table->dropColumn('marketer_id');
        });
    }
};
