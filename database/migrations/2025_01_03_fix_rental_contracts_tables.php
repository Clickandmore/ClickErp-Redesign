<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixRentalContractsTablesV1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. التحقق من جدول rental_contracts
        if (!Schema::hasTable('rental_contracts')) {
            Schema::create('rental_contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unsignedBigInteger('maid_id')->nullable();
                $table->foreign('maid_id')->references('id')->on('maids')->onDelete('set null');
                $table->unsignedBigInteger('marketer_id')->nullable();
                $table->foreign('marketer_id')->references('id')->on('admins')->onDelete('set null');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
                $table->string('status')->default('new');
                $table->date('contract_date')->nullable();
                $table->string('contract_number')->nullable()->unique();
                $table->enum('warranty_status', ['not_applicable', 'active', 'expired'])->default('not_applicable');
                $table->date('warranty_start_date')->nullable();
                $table->date('warranty_end_date')->nullable();
                $table->timestamps();
            });
        }

        // 2. التحقق من جدول rental_contract_infos
        if (!Schema::hasTable('rental_contract_infos')) {
            Schema::create('rental_contract_infos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rental_contract_id');
                $table->foreign('rental_contract_id')->references('id')->on('rental_contracts')->onDelete('cascade');
                $table->integer('working_hours_per_day')->nullable();
                $table->decimal('hourly_rate', 8, 2)->nullable();
                $table->decimal('monthly_salary', 10, 2)->nullable();
                $table->decimal('sponsorship_transfer_amount', 10, 2)->nullable();
                $table->decimal('salary_after_sponsorship', 10, 2)->nullable();
                $table->decimal('monthly_cost', 10, 2)->nullable();
                $table->integer('contract_duration_months')->nullable();
                $table->integer('contract_duration_days')->nullable();
                $table->decimal('contract_value', 12, 2)->nullable();
                $table->decimal('total_cost', 12, 2)->nullable();
                $table->date('contract_date')->nullable();
                $table->date('ingaz_from')->nullable();
                $table->date('ingaz_to')->nullable();
                $table->text('notes')->nullable();
                $table->json('additional_terms')->nullable();
                $table->string('sponsorship_transfer')->nullable();
                $table->string('rental_type')->nullable();
                $table->timestamps();
            });
        } else {
            // إضافة الأعمدة المفقودة إذا كان الجدول موجوداً
            Schema::table('rental_contract_infos', function (Blueprint $table) {
                if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer')) {
                    $table->string('sponsorship_transfer')->nullable();
                }
                if (!Schema::hasColumn('rental_contract_infos', 'rental_type')) {
                    $table->string('rental_type')->nullable();
                }
                if (!Schema::hasColumn('rental_contract_infos', 'total_cost')) {
                    $table->decimal('total_cost', 12, 2)->nullable();
                }
                if (!Schema::hasColumn('rental_contract_infos', 'contract_date')) {
                    $table->date('contract_date')->nullable();
                }
            });
        }

        // 3. التحقق من جدول rental_contract_terms
        if (!Schema::hasTable('rental_contract_terms')) {
            Schema::create('rental_contract_terms', function (Blueprint $table) {
                $table->id();
                $table->string('section_title')->nullable();
                $table->integer('section_order')->default(0);
                $table->string('term_number')->nullable();
                $table->text('term_content');
                $table->integer('term_order')->default(0);
                $table->integer('page_number')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['page_number', 'is_active']);
                $table->index('section_order');
                $table->index('term_order');
            });
        }

        // 4. وضع علامة في جدول migrations أن هذه migrations تمت
        $migrations = [
            '2025_07_30_153014_create_rental_contracts_table',
            '2025_07_30_153055_create_rental_contract_infos_table',
            '2025_07_30_153108_add_rental_fields_to_users_table',
            '2025_08_03_115151_add_missing_fields_to_rental_contract_infos_table',
            '2025_01_02_000000_create_rental_contract_terms_table'
        ];

        $batch = DB::table('migrations')->max('batch') ?? 0;
        foreach ($migrations as $migration) {
            DB::table('migrations')->updateOrInsert(
                ['migration' => $migration],
                ['migration' => $migration, 'batch' => $batch + 1]
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // لا نريد حذف الجداول في down لأنها قد تحتوي على بيانات مهمة
    }
}
