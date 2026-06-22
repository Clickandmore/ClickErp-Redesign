<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMoyasarSettingsTable extends Migration
{
    public function up(): void
    {
        Schema::create('moyasar_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('base_url')->default('https://api.moyasar.com/v1');
            $table->string('publishable_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moyasar_settings');
    }
}
