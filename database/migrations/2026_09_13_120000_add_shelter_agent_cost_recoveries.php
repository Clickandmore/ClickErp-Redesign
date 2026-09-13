<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shelter_cost_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('office_account_id')->nullable();
            $table->unsignedBigInteger('supplier_constraint_id')->nullable();
            $table->unsignedBigInteger('asset_account_id')->nullable();
        });
        Schema::create('shelter_agent_cost_recoveries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('maid_id')->index();
            $table->unsignedBigInteger('receipt_entry_id')->index();
            $table->uuid('request_key')->unique();
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->text('reason');
            $table->unsignedBigInteger('office_account_id');
            $table->unsignedBigInteger('asset_account_id');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedBigInteger('constraint_id')->nullable();
            $table->unsignedBigInteger('reversal_constraint_id')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('reversed_by_id')->nullable();
            $table->timestamps();
            $table->foreign('receipt_entry_id', 'shelter_recovery_receipt_fk')->references('id')->on('shelter_cost_entries')->restrictOnDelete();
            $table->foreign('constraint_id', 'shelter_recovery_constraint_fk')->references('id')->on('fn_constraints')->restrictOnDelete();
            $table->foreign('reversal_constraint_id', 'shelter_recovery_reversal_fk')->references('id')->on('fn_constraints')->restrictOnDelete();
        });
        foreach (['shelter_agent_cost_recovery' => 'استرداد تكلفة الوكيل من الإيواء', 'shelter_agent_cost_recovery_reverse' => 'عكس استرداد تكلفة الوكيل'] as $name => $label) {
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], [
                'display_name' => $label, 'group_name' => 'shelter_contract', 'section_name' => 'shelter_management',
            ]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('shelter_agent_cost_recoveries');
        Schema::table('shelter_cost_entries', function (Blueprint $table) {
            $table->dropColumn(['office_account_id', 'supplier_constraint_id', 'asset_account_id']);
        });
        DB::table('permissions')->where('guard_name', 'admin')->whereIn('name', ['shelter_agent_cost_recovery', 'shelter_agent_cost_recovery_reverse'])->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
