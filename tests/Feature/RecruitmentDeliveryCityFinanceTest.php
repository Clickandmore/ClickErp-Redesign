<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Services\ingaz\RecruitmentOrderClientCompletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecruitmentDeliveryCityFinanceTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'delivery_city_test');
            $app['config']->set('database.connections.delivery_city_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });
        Schema::create('fn_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('city')->nullable();
            $table->boolean('is_company')->default(false);
            $table->string('tax_number')->nullable();
            $table->timestamps();
        });
        DB::table('fn_clients')->insert([
            ['id' => 1, 'account_id' => 10, 'city' => 'Riyadh'],
            ['id' => 2, 'account_id' => 20, 'city' => 'Jeddah'],
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('delivery_city_test');
        parent::tearDown();
    }

    public function test_delivery_city_updates_existing_finance_record_for_both_contract_forms(): void
    {
        foreach ([['new_client_city' => ' Dammam '], ['order_id' => 1, 'client_city' => ' Dammam ']] as $input) {
            foreach ([null, 'Riyadh', 'Dammam'] as $oldCity) {
                DB::table('users')->updateOrInsert(['id' => 1], ['account_id' => 10, 'city' => $oldCity]);
                DB::table('fn_clients')->where('id', 1)->update(['city' => 'Riyadh']);
                $user = (new User())->newFromBuilder(['id' => 1, 'account_id' => 10, 'city' => $oldCity]);
                User::withoutEvents(fn () => app(RecruitmentOrderClientCompletionService::class)
                    ->saveDeliveryCity($user, new Request($input)));
                $this->assertSame('Dammam', DB::table('users')->where('id', 1)->value('city'));
                $this->assertSame('Dammam', DB::table('fn_clients')->where('id', 1)->value('city'));
                $this->assertSame('Jeddah', DB::table('fn_clients')->where('id', 2)->value('city'));
                $contract = new Contract();
                $contract->setRelation('user', $user);
                $this->assertSame('Dammam', $contract->resolveExternalOfficeDispatchArrivalStation());
            }
        }
    }

    public function test_blank_city_preserves_existing_finance_city(): void
    {
        $user = new User(['account_id' => 10, 'city' => 'Riyadh']);
        app(RecruitmentOrderClientCompletionService::class)->saveDeliveryCity($user, new Request(['new_client_city' => '  ']));
        $this->assertSame('Riyadh', DB::table('fn_clients')->where('id', 1)->value('city'));
    }

    public function test_invoice_refreshes_stale_finance_city_without_creating_another_client(): void
    {
        $controller = (new \ReflectionClass(InvoiceController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(InvoiceController::class, 'ensureFinanceClientForInvoice');
        $method->setAccessible(true);
        $user = new User(['account_id' => 10, 'city' => 'Dammam', 'client_type' => 'person']);
        \Nami\Finance\Models\Client::withoutEvents(fn () => $method->invoke($controller, $user, 10));
        $this->assertSame('Dammam', DB::table('fn_clients')->where('id', 1)->value('city'));
        $this->assertSame(2, DB::table('fn_clients')->count());
        $user->city = null;
        \Nami\Finance\Models\Client::withoutEvents(fn () => $method->invoke($controller, $user, 10));
        $this->assertSame('Dammam', DB::table('fn_clients')->where('id', 1)->value('city'));
    }
}
