<?php
namespace Tests\Feature\Concerns;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
trait BuildsHourlyWorkerSchema {
    private function buildWorkers(): void {
        Schema::create('maids',function(Blueprint $t){ $t->boolean('has_driving_license')->nullable(); $t->string('driving_license_file')->nullable();$t->id();$t->string('name');$t->string('passport_num')->nullable();$t->string('status')->default('available');$t->timestamps();$t->softDeletes();});
        Schema::create('maid_translations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->string('locale');});
        Schema::create('job_types',function(Blueprint $t){$t->id();});
        Schema::create('job_types_translations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('job_type_id');$t->string('title');$t->string('locale');});
        DB::table('job_types')->insert(['id'=>2]);
        DB::table('job_types_translations')->insert(['job_type_id'=>2,'title'=>'تأجير','locale'=>'ar']);

        (require base_path('database/migrations/2026_09_17_000006_add_hourly_settings_to_maids.php'))->up();
        (require base_path('database/migrations/2026_09_17_000007_add_hourly_suspension_to_maids.php'))->up();
        Schema::table('maids',function(Blueprint $t){$t->string('rental_source')->nullable();$t->date('arrival_date')->nullable();$t->unsignedBigInteger('auto_recruitment_contract_id')->nullable();$t->unsignedBigInteger('nationality_id')->nullable();$t->string('phone_1')->nullable();$t->string('id_number')->nullable();});
        Schema::create('maid_jobs',function(Blueprint $t){$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('job_type_id');});
        Schema::create('contracts',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('previous_contract_id')->nullable();$t->string('status');$t->softDeletes();});
        Schema::create('contract_infos',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->unsignedBigInteger('shelter_id')->nullable();$t->boolean('is_outside_shelter')->nullable();$t->string('wants_to_work')->nullable();});
        Schema::create('contract_status_logs',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->string('status');$t->timestamp('date');$t->text('notes');});
        Schema::create('rental_contracts',function(Blueprint $t){ $t->boolean('freeze_releases_maid')->default(false);$t->id();$t->unsignedBigInteger('maid_id');$t->string('status');$t->string('closure_type')->nullable();$t->timestamp('returned_to_company_at')->nullable();$t->timestamp('closed_at')->nullable();$t->timestamps();$t->softDeletes();});
        Schema::create('sponsorship_transfers',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->string('status');$t->timestamp('maid_delivered_at')->nullable();$t->softDeletes();});
        Schema::create('deportation_procedures',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->string('status');$t->timestamp('completed_at')->nullable();});
        Schema::create('deportation_procedure_logs',function(Blueprint $t){$t->id();$t->unsignedBigInteger('deportation_procedure_id');$t->string('action_type');$t->timestamp('created_at');});

    }
}