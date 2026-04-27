<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crm_questions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('choices');
            $table->foreignId('poll_id')->constrained('crm_polls')->onDelete('cascade');
            $table->boolean('is_show')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_questions');
    }
};
