<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_escape_reports', function (Blueprint $table) {
            $table->date('closed_without_return_date')->nullable();
            $table->timestamp('closed_without_return_at')->nullable();
            $table->unsignedBigInteger('closed_without_return_by')->nullable();
            $table->text('no_return_reason')->nullable();
            $table->json('no_return_attachment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rental_escape_reports', function (Blueprint $table) {
            $table->dropColumn(['closed_without_return_date', 'closed_without_return_at', 'closed_without_return_by', 'no_return_reason', 'no_return_attachment']);
        });
    }
};
