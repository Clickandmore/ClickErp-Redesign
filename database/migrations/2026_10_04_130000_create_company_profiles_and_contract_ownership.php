<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('setting_id')->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('tax_number', 50)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->string('license_number', 100)->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->string('representative')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->timestamps();
        });
        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('company_profile_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_company_user_id')->nullable()->index();
            $table->unsignedBigInteger('company_finance_account_id')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('archived_company_profile_id')->nullable()->index();
        });
        if (Schema::hasTable('sponsorship_transfers')) {
            Schema::table('sponsorship_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('old_company_profile_id')->nullable()->index();
                $table->unsignedBigInteger('old_company_finance_account_id')->nullable();
                $table->unsignedBigInteger('old_sponsor_id')->nullable()->change();
            });
        }
        if (Schema::hasTable('transfer_service_contracts')) {
            Schema::table('transfer_service_contracts', function (Blueprint $table) {
                $table->unsignedBigInteger('old_sponsor_id')->nullable()->change();
            });
        }
        Schema::create('company_contract_migrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_profile_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->json('source_user_ids');
            $table->json('contract_ids');
            $table->json('preview');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Ownership data must be reversed explicitly before dropping the schema.
        if (\Illuminate\Support\Facades\DB::table('contracts')->whereNotNull('company_profile_id')->exists()) {
            throw new RuntimeException('Cannot remove company ownership while company contracts exist.');
        }
        if (\Illuminate\Support\Facades\DB::table('users')->whereNotNull('archived_company_profile_id')->exists()) {
            throw new RuntimeException('Cannot remove company ownership while migrated historical customers exist.');
        }
        if (Schema::hasColumn('sponsorship_transfers', 'old_company_profile_id')) {
            if (\Illuminate\Support\Facades\DB::table('sponsorship_transfers')->whereNotNull('old_company_profile_id')->exists()) {
                throw new RuntimeException('Cannot remove company ownership while company transfers exist.');
            }
            Schema::table('sponsorship_transfers', fn (Blueprint $table) => $table->dropColumn(['old_company_profile_id', 'old_company_finance_account_id']));
        }
        Schema::dropIfExists('company_contract_migrations');
        Schema::table('contracts', fn (Blueprint $table) => $table->dropColumn(['company_profile_id', 'legacy_company_user_id', 'company_finance_account_id']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('archived_company_profile_id'));
        Schema::dropIfExists('company_profiles');
    }
};
