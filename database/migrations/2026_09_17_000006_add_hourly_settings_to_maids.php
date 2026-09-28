<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('maids', function (Blueprint $t) {
            $t->string('rental_mode', 20)->default('regular')->index();
            $t->json('hourly_settings')->nullable();
            $t->string('hourly_username')->nullable()->unique();
            $t->string('hourly_login_phone', 20)->nullable()->unique();
            $t->string('hourly_email')->nullable();
            $t->string('hourly_password')->nullable();
            $t->unsignedInteger('hourly_auth_version')->default(1);
        });
    }
    public function down(): void {
        Schema::table('maids', function (Blueprint $t) {
            $t->dropColumn(['rental_mode','hourly_settings','hourly_username','hourly_login_phone','hourly_email','hourly_password','hourly_auth_version']);
        });
    }
};
