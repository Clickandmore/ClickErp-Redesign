<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            $table->json('renewal_quote')->nullable();
            $table->date('preferred_return_date')->nullable();
            $table->text('response_notes')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('rental_contract_expiry_reminders', fn (Blueprint $table) => $table->dropColumn(['renewal_quote', 'preferred_return_date', 'response_notes']));
    }
};
