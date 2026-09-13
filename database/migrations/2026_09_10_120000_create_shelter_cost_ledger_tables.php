<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * دفتر الإيواء المستقل + أعمدة الربط التي تحمي قيود الإرجاع والترحيل من التكرار.
 * كل خطوة محمية بـ hasTable/hasColumn حتى تعمل على قواعد مُهاجَرة جزئياً.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shelter_cost_entries')) {
            Schema::create('shelter_cost_entries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('maid_id')->index();
                $table->unsignedBigInteger('contract_id')->nullable()->index();
                // capitalize_office_cost | living_expense | release_to_trial | return_from_trial
                // | release_to_deportation | release_to_sale | write_off | legacy_adjustment
                $table->string('kind', 40)->index();
                // موجب = يزيد رصيد أصل الإيواء للعاملة، سالب = يُفرج عنه
                $table->decimal('amount', 14, 2);
                $table->unsignedBigInteger('constraint_id')->nullable()->index();
                $table->string('source_type', 120)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['source_type', 'source_id', 'kind'], 'shelter_cost_entries_source_unique');
                $table->foreign('constraint_id', 'shelter_cost_entries_constraint_fk')
                    ->references('id')->on('fn_constraints')->nullOnDelete();
            });
        }

        if (Schema::hasTable('deportation_procedures') && !Schema::hasColumn('deportation_procedures', 'constraint_id')) {
            Schema::table('deportation_procedures', function (Blueprint $table) {
                $table->unsignedBigInteger('constraint_id')->nullable()->after('completed_by');
                $table->foreign('constraint_id', 'deportation_procedures_constraint_fk')
                    ->references('id')->on('fn_constraints')->nullOnDelete();
            });
        }

        if (Schema::hasTable('deportation_procedures') && !Schema::hasColumn('deportation_procedures', 'cancel_reason')) {
            Schema::table('deportation_procedures', function (Blueprint $table) {
                $table->text('cancel_reason')->nullable()->after('notes');
                $table->timestamp('cancelled_at')->nullable()->after('completed_at');
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('completed_by');
            });
        }

        if (Schema::hasTable('contract_return_requests')) {
            Schema::table('contract_return_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('contract_return_requests', 'credit_note_id')) {
                    $table->unsignedBigInteger('credit_note_id')->nullable()->after('processed_at');
                }
                if (!Schema::hasColumn('contract_return_requests', 'return_constraint_id')) {
                    $table->unsignedBigInteger('return_constraint_id')->nullable()->after('credit_note_id');
                    $table->foreign('return_constraint_id', 'contract_return_requests_return_constraint_fk')
                        ->references('id')->on('fn_constraints')->nullOnDelete();
                }
                if (!Schema::hasColumn('contract_return_requests', 'shelter_constraint_id')) {
                    $table->unsignedBigInteger('shelter_constraint_id')->nullable()->after('return_constraint_id');
                    $table->foreign('shelter_constraint_id', 'contract_return_requests_shelter_constraint_fk')
                        ->references('id')->on('fn_constraints')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('shelter_living_expenses') && !Schema::hasColumn('shelter_living_expenses', 'reversed_by_constraint_id')) {
            Schema::table('shelter_living_expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('reversed_by_constraint_id')->nullable()->after('constraint_id');
                $table->timestamp('reversed_at')->nullable()->after('reversed_by_constraint_id');
                $table->foreign('reversed_by_constraint_id', 'shelter_living_expenses_reversal_fk')
                    ->references('id')->on('fn_constraints')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shelter_living_expenses') && Schema::hasColumn('shelter_living_expenses', 'reversed_by_constraint_id')) {
            Schema::table('shelter_living_expenses', function (Blueprint $table) {
                $table->dropForeign('shelter_living_expenses_reversal_fk');
                $table->dropColumn(['reversed_by_constraint_id', 'reversed_at']);
            });
        }

        if (Schema::hasTable('contract_return_requests')) {
            Schema::table('contract_return_requests', function (Blueprint $table) {
                if (Schema::hasColumn('contract_return_requests', 'shelter_constraint_id')) {
                    $table->dropForeign('contract_return_requests_shelter_constraint_fk');
                    $table->dropColumn('shelter_constraint_id');
                }
                if (Schema::hasColumn('contract_return_requests', 'return_constraint_id')) {
                    $table->dropForeign('contract_return_requests_return_constraint_fk');
                    $table->dropColumn('return_constraint_id');
                }
                if (Schema::hasColumn('contract_return_requests', 'credit_note_id')) {
                    $table->dropColumn('credit_note_id');
                }
            });
        }

        if (Schema::hasTable('deportation_procedures')) {
            Schema::table('deportation_procedures', function (Blueprint $table) {
                if (Schema::hasColumn('deportation_procedures', 'constraint_id')) {
                    $table->dropForeign('deportation_procedures_constraint_fk');
                    $table->dropColumn('constraint_id');
                }
                if (Schema::hasColumn('deportation_procedures', 'cancel_reason')) {
                    $table->dropColumn(['cancel_reason', 'cancelled_at', 'cancelled_by']);
                }
            });
        }

        Schema::dropIfExists('shelter_cost_entries');
    }
};
