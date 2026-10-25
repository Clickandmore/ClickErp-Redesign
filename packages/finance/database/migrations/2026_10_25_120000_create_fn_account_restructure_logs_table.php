<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجل تدقيق لعمليات إعادة هيكلة الدليل المحاسبي (حذف/نقل/دمج).
 * الحساب المحذوف أو المدموج يختفي من fn_accounts، فيُحفظ وصفه وما تأثر به هنا.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fn_account_restructure_logs')) {
            return;
        }

        Schema::create('fn_account_restructure_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 20);
            $table->unsignedBigInteger('account_id')->index();
            $table->string('account_code', 32)->nullable();
            $table->string('account_name')->nullable();
            $table->unsignedBigInteger('target_account_id')->nullable()->index();
            $table->unsignedBigInteger('old_parent_id')->nullable();
            $table->unsignedBigInteger('new_parent_id')->nullable();
            $table->string('reason', 1000)->nullable();
            $table->json('snapshot')->nullable();
            $table->json('details')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fn_account_restructure_logs');
    }
};
