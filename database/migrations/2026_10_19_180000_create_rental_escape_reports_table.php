<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE rental_contracts MODIFY closure_type ENUM('available','transferred','escape') NULL");
        }
        Schema::create('rental_escape_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_contract_id')->unique();
            $table->unsignedBigInteger('maid_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->date('escape_date');
            $table->string('reference_number')->nullable();
            $table->text('notes');
            $table->json('attachment')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('return_notes')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
            $table->index(['maid_id', 'ready_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_escape_reports');
    }
};
