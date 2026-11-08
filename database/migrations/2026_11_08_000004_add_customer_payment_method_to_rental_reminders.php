<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            $table->string('customer_payment_method', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_expiry_reminders', fn (Blueprint $table) => $table->dropColumn('customer_payment_method'));
    }
};
