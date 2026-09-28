<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('maids', fn(Blueprint $t) => $t->boolean('hourly_is_suspended')->default(false));
    }
    public function down(): void {
        Schema::table('maids', fn(Blueprint $t) => $t->dropColumn('hourly_is_suspended'));
    }
};
