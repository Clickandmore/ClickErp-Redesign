<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_financial_adjustments', function (Blueprint $table) {
            $table->decimal('calculated_amount', 12, 2)->nullable()->after('amount');
            $table->string('manual_override_reason', 1000)->nullable()->after('calculated_amount');
            $table->string('manual_override_attachment_path')->nullable()->after('manual_override_reason');
            $table->foreignId('manual_override_by')->nullable()->after('manual_override_attachment_path')
                ->constrained('admins')->nullOnDelete();
            $table->foreignId('manual_override_approved_by')->nullable()->after('manual_override_by')
                ->constrained('admins')->nullOnDelete();
            $table->timestamp('manual_override_approved_at')->nullable()->after('manual_override_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('rental_financial_adjustments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manual_override_approved_by');
            $table->dropConstrainedForeignId('manual_override_by');
            $table->dropColumn([
                'calculated_amount',
                'manual_override_reason',
                'manual_override_attachment_path',
                'manual_override_approved_at',
            ]);
        });
    }
};
