<?php

namespace Tests\Unit;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\PresenceVerifierInterface;
use Nami\Finance\Http\Requests\SupplierRequest;
use Nami\Finance\Services\SupplierService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SupplierOptionalDetailsTest extends TestCase
{
    public function test_create_and_update_accept_blank_optional_details_but_validate_supplied_values(): void
    {
        $factory = new Factory(new Translator(new ArrayLoader(), 'en'));
        $presence = $this->createMock(PresenceVerifierInterface::class);
        $presence->method('getCount')->willReturn(0);
        $factory->setPresenceVerifier($presence);

        foreach (['POST', 'PUT'] as $method) {
            $request = SupplierRequest::create('/finance/suppliers', $method);
            $data = ['name' => 'Supplier', 'code' => '2101', 'phone' => '0500000000', 'supplier_type_name' => 'General'];
            $this->assertTrue($factory->make($data, $request->rules())->passes());
            $blank = ['address' => null, 'email' => null, 'commercial_register_number' => null, 'credit_limit' => null];
            $this->assertTrue($factory->make($data + $blank, $request->rules())->passes());
            $validator = $factory->make($data + ['email' => 'invalid', 'credit_limit' => 'invalid'], $request->rules());
            $this->assertTrue($validator->errors()->has('email'));
            $this->assertTrue($validator->errors()->has('credit_limit'));
        }
    }

    public function test_blank_details_use_database_safe_defaults_and_omitted_update_details_stay_omitted(): void
    {
        $service = $this->getMockBuilder(SupplierService::class)
            ->disableOriginalConstructor()->onlyMethods(['resolveSupplierTypeId'])->getMock();
        $service->method('resolveSupplierTypeId')->willReturn(1);
        $prepare = new ReflectionMethod(SupplierService::class, 'prepareSupplierData');
        $prepare->setAccessible(true);
        $result = $prepare->invoke($service, ['email' => null, 'address' => '', 'commercial_register_number' => null, 'credit_limit' => null]);
        $this->assertSame('', $result['email']);
        $this->assertSame('', $result['address']);
        $this->assertSame(0, $result['commercial_register_number']);
        $this->assertSame(0, $result['credit_limit']);
        $this->assertArrayNotHasKey('email', $prepare->invoke($service, ['name' => 'Updated']));
    }
}
