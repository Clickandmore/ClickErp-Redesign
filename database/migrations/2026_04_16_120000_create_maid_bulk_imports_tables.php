<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('maid_bulk_imports')) {
            Schema::create('maid_bulk_imports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('status', 50)->default('pending_review');
                $table->string('original_filename')->nullable();
                $table->string('uploaded_file_path')->nullable();
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_rows')->default(0);
                $table->unsignedInteger('invalid_rows')->default(0);
                $table->unsignedInteger('imported_rows')->default(0);
                $table->unsignedInteger('failed_rows')->default(0);
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('uploaded_by', 'maid_bulk_imports_uploaded_by_fk')
                    ->references('id')
                    ->on('admins')
                    ->onDelete('set null');

                $table->foreign('approved_by', 'maid_bulk_imports_approved_by_fk')
                    ->references('id')
                    ->on('admins')
                    ->onDelete('set null');
            });
        }

        if (!Schema::hasTable('maid_bulk_import_rows')) {
            Schema::create('maid_bulk_import_rows', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('maid_bulk_import_id');
                $table->unsignedInteger('row_number');
                $table->string('status', 50)->default('pending');
                $table->unsignedBigInteger('maid_id')->nullable();
                $table->timestamp('imported_at')->nullable();
                $table->json('raw_data')->nullable();
                $table->json('normalized_data')->nullable();
                $table->json('error_messages')->nullable();
                $table->timestamps();

                $table->foreign('maid_bulk_import_id', 'maid_bulk_import_rows_import_fk')
                    ->references('id')
                    ->on('maid_bulk_imports')
                    ->onDelete('cascade');

                $table->foreign('maid_id', 'maid_bulk_import_rows_maid_fk')
                    ->references('id')
                    ->on('maids')
                    ->onDelete('set null');

                $table->index(['maid_bulk_import_id', 'status'], 'maid_bulk_import_rows_import_status_idx');
                $table->index(['maid_bulk_import_id', 'row_number'], 'maid_bulk_import_rows_import_row_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maid_bulk_import_rows');
        Schema::dropIfExists('maid_bulk_imports');
    }
};
