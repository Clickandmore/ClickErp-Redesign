<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\RentalContract;
use App\Models\ingaz\Maid;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RentalExtensionPackageSelectionTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'extension_package_test');
            $app['config']->set('database.connections.extension_package_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    public function test_selection_preserves_default_and_rejects_unavailable_packages(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->integer('nationality_id')->nullable();
            $table->boolean('is_show')->nullable();
            $table->string('rental_type')->default('monthly');
            $table->string('duration_unit')->default('month');
            $table->integer('days_count')->default(30);
            $table->decimal('price_with_tax')->default(3300);
        });
        Schema::create('packages_translations', function (Blueprint $table) {
            $table->id();
            $table->integer('package_id');
            $table->string('locale');
            $table->string('title');
        });
        $contract = new RentalContract;
        $contract->setRelation('maid', new Maid(['nationality_id' => 1]));
        $reflection = new \ReflectionClass(RentContractController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('resolveExtensionPackage');
        $method->setAccessible(true);
        $this->assertNull($method->invoke($controller, new Request, $contract));
        foreach ([['nationality_id' => 1], ['nationality_id' => null]] as $attributes) {
            $id = DB::table('packages')->insertGetId($attributes);
            $this->assertEquals($id, $method->invoke($controller, new Request(['extension_package_id' => $id]), $contract)->id);
        }
        foreach ([['nationality_id' => 2], ['is_show' => false], ['rental_type' => 'hourly'], ['days_count' => 0], ['price_with_tax' => 0]] as $attributes) {
            $id = DB::table('packages')->insertGetId($attributes);
            try {
                $method->invoke($controller, new Request(['extension_package_id' => $id]), $contract);
                $this->fail('Unavailable package accepted');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('extension_package_id', $exception->errors());
            }
        }
    }
}
