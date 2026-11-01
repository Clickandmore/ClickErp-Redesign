<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * fn_account_code_aliases: كود قديم يبحث عنه البرنامج ← رقم الحساب بعد إعادة ترقيم الدليل. فارغ = البحث بالكود كما كان.
 * fn_chart_restructure_runs: سجل تنفيذ خطط finance:chart-restructure، يمنع تنفيذ الخطة نفسها مرتين ويحفظ خريطة الأكواد.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_account_code_aliases')) {
            Schema::create('fn_account_code_aliases', function (Blueprint $table) {
                $table->id();
                $table->string('legacy_code', 32)->unique();
                $table->unsignedBigInteger('account_id')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('fn_chart_restructure_runs')) {
            Schema::create('fn_chart_restructure_runs', function (Blueprint $table) {
                $table->id();
                $table->string('plan', 100)->index();
                $table->string('plan_hash', 64);
                $table->json('summary')->nullable();
                $table->longText('mapping')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fn_chart_restructure_runs');
        Schema::dropIfExists('fn_account_code_aliases');
    }
};
