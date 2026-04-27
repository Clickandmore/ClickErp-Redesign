<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fn_principles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->nullable()->constrained('fn_accounts')->onDelete('cascade');
            $table->date('registration_date');
            $table->double('monthly_value');
            $table->double('junk_value')->nullable()->default(0);
            $table->string('hypothetical_years')->nullable()->default(0);
            $table->string('perished_type')->nullable();
            $table->string('perished_value')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('perished_account_id')->nullable()->constrained('fn_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_principles');
    }
};
