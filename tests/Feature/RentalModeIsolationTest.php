<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RentContractController;
use App\Http\Requests\StoreRentalContractRequest;
use App\Models\ingaz\Maid;
use App\Services\Hourly\MaidSettingsService;
use App\Services\ingaz\RentalContractFinanceService;
use App\Services\Rental\RentalModeGuard;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema, Validator};
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase;

class RentalModeIsolationTest extends HourlyTestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        $this->buildWorkers();
        Schema::table('maids',function(Blueprint $t){$t->boolean('available')->default(true);$t->unsignedBigInteger('branch_id')->nullable();});
        Schema::create('orders',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id')->nullable();$t->string('status');$t->softDeletes();});
        Schema::create('rental_contract_infos',function(Blueprint $t){$t->id();$t->unsignedBigInteger('rental_contract_id');});
        Schema::create('rental_contract_invoices',function(Blueprint $t){$t->id();$t->unsignedBigInteger('rental_contract_id');$t->unsignedBigInteger('receipt_bond_id')->nullable();});
        DB::table('maids')->insert([
            ['id'=>1,'name'=>'Hourly worker','rental_mode'=>'hourly','passport_num'=>'P1','branch_id'=>1],
            ['id'=>2,'name'=>'Monthly worker','rental_mode'=>'regular','passport_num'=>'P2','branch_id'=>1],
            ['id'=>3,'name'=>'Other branch hourly worker','rental_mode'=>'hourly','passport_num'=>'P3','branch_id'=>2],
        ]);
        foreach([1,2,3] as $id){
            DB::table('maid_jobs')->insert(['maid_id'=>$id,'job_type_id'=>2]);
            DB::table('contracts')->insert(['id'=>$id,'maid_id'=>$id,'status'=>'shelter']);
            DB::table('contract_infos')->insert(['contract_id'=>$id,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        }
    }

    protected function tearDown(): void
    {
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    public function test_monthly_selection_excludes_hourly_workers_in_create_and_replacement(): void
    {
        $controller=app(RentContractController::class);
        $method=new \ReflectionMethod($controller,'getAvailableRentalMaidsQuery');
        $method->setAccessible(true);
        $this->assertSame([2],$method->invoke($controller)->pluck('maids.id')->all());
        $this->assertSame([2],$method->invoke($controller,1)->pluck('maids.id')->all());
        $this->assertEqualsCanonicalizing([1,2],$method->invoke($controller,1,true)->pluck('maids.id')->all());
    }

    public function test_direct_monthly_create_is_rejected_before_any_contract_is_written(): void
    {
        $request=StoreRentalContractRequest::create('/contracts','POST',['maid_id'=>1]);
        try {
            app(RentContractController::class)->store($request,\Mockery::mock(RentalContractFinanceService::class));
            $this->fail('Hourly worker accepted by monthly create');
        } catch(ValidationException $e) {
            $this->assertSame([RentalModeGuard::MONTHLY_MESSAGE],$e->errors()['maid_id']);
        }
        $this->assertSame(0,DB::table('rental_contracts')->count());
    }

    public function test_form_validation_rejects_hourly_worker_but_allows_legacy_contract_maintenance(): void
    {
        $request=StoreRentalContractRequest::create('/contracts','POST',['maid_id'=>1,'payment_status'=>'pending']);
        $validator=Validator::make([],[]);$request->withValidator($validator);
        $this->assertTrue($validator->fails());
        $this->assertContains(RentalModeGuard::MONTHLY_MESSAGE,$validator->errors()->get('maid_id'));

        DB::table('rental_contracts')->insert(['id'=>1,'maid_id'=>1,'status'=>'contract_sent']);
        $request=StoreRentalContractRequest::create('/contracts/1','PUT',['maid_id'=>1,'payment_status'=>'pending']);
        $route=new \Illuminate\Routing\Route('PUT','contracts/{id}',['as'=>'RentContract.update']);
        $route->bind($request);$request->setRouteResolver(fn()=>$route);
        $validator=Validator::make([],[]);$request->withValidator($validator);$validator->fails();
        $this->assertFalse($validator->errors()->has('maid_id'));
    }

    public function test_receipt_upload_on_historical_contract_allows_unchanged_maid_with_another_active_contract(): void
    {
        DB::table('rental_contracts')->insert(['id' => 10, 'maid_id' => 2, 'status' => 'active']);
        DB::table('maids')->where('id', 2)->update(['available' => false]);

        foreach (['cancelled', 'completed'] as $status) {
            DB::table('rental_contracts')->updateOrInsert(['id' => 11], ['maid_id' => 2, 'status' => $status]);
            foreach (['PUT', 'PATCH'] as $method) {
                $request = StoreRentalContractRequest::create('/contracts/11', $method, [
                    'maid_id' => '2', 'payment_type' => 'bank_transfer', 'payment_status' => 'bank_pending',
                ], [], ['payment_receipt' => \Illuminate\Http\UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf')]);
                $route = new \Illuminate\Routing\Route($method, 'contracts/{RentContract}', ['as' => 'RentContract.update']);
                $route->bind($request);
                $request->setRouteResolver(fn () => $route);
                $validator = Validator::make($request->all(), ['payment_receipt' => 'file|mimes:pdf']);
                $request->withValidator($validator);

                $this->assertTrue($validator->passes(), $validator->errors()->toJson());
                $this->assertSame('active', DB::table('rental_contracts')->where('id', 10)->value('status'));
                $this->assertEquals(0, DB::table('maids')->where('id', 2)->value('available'));
            }
        }
    }

    public function test_new_contract_or_changed_maid_still_rejects_an_existing_active_rental(): void
    {
        DB::table('rental_contracts')->insert([
            ['id' => 10, 'maid_id' => 2, 'status' => 'active'],
            ['id' => 11, 'maid_id' => 1, 'status' => 'cancelled'],
        ]);

        foreach (['POST', 'PUT', 'PATCH'] as $method) {
            $request = StoreRentalContractRequest::create('/contracts/11', $method, [
                'maid_id' => 2, 'payment_status' => 'pending',
            ]);
            if ($method !== 'POST') {
                $route = new \Illuminate\Routing\Route($method, 'contracts/{RentContract}', ['as' => 'RentContract.update']);
                $route->bind($request);
                $request->setRouteResolver(fn () => $route);
            }
            $validator = Validator::make([], []);
            $request->withValidator($validator);

            $this->assertTrue($validator->fails());
            $this->assertContains('العاملة المختارة مرتبطة بعقد إيجار نشط حالياً', $validator->errors()->get('maid_id'));
        }
    }

    public function test_conversion_rejects_frozen_monthly_contract_in_validation_and_save(): void
    {
        DB::table('rental_contracts')->insert(['maid_id'=>2,'status'=>'frozen']);
        $maid=Maid::findOrFail(2);$service=app(MaidSettingsService::class);
        try {
            $service->validate(new Request(['rental_mode'=>'hourly','type_id'=>2]),$maid);
            $this->fail('Frozen contract did not block conversion');
        } catch(ValidationException $e){$this->assertArrayHasKey('rental_mode',$e->errors());}
        try {
            $service->save($maid,['mode'=>'hourly','data'=>[]]);
            $this->fail('Save bypassed the conversion guard');
        } catch(ValidationException $e){$this->assertArrayHasKey('rental_mode',$e->errors());}
        $this->assertSame('regular',$maid->fresh()->rental_mode);
        DB::table('rental_contracts')->update(['status'=>'completed']);
        app(RentalModeGuard::class)->assertHourlyConversion($maid);
        $this->assertSame('regular',$maid->fresh()->rental_mode);
    }

    public function test_monthly_selection_still_respects_existing_branch_scope(): void
    {
        Maid::addGlobalScope('test_branch',fn($q)=>$q->where('maids.branch_id',1));
        $query=app(RentalModeGuard::class)->applyMonthlySelection(Maid::query(),3,true);
        $this->assertSame([2],$query->pluck('id')->all());
    }

    public function test_regular_rental_selection_works_without_hourly_module_columns(): void
    {
        Schema::table('maids',fn(Blueprint $t)=>$t->dropColumn('rental_mode'));
        $query=app(RentalModeGuard::class)->applyMonthlySelection(Maid::query());
        $this->assertSame(3,$query->count());
    }
}
