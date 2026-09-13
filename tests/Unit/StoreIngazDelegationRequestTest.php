<?php

namespace Tests\Unit;

use App\Http\Requests\StoreIngazDelegationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreIngazDelegationRequestTest extends TestCase
{
    public function test_creation_requires_client_and_worker_identity_fields(): void
    {
        $request = StoreIngazDelegationRequest::create('/admin/ingaz/ingaz-delegations', 'POST', [
            'client_source_type' => 'separate_client',
        ]);
        $rules = $request->rules();

        $validator = Validator::make(
            ['client_source_type' => 'separate_client'],
            [
                'separate_client_identity_number' => $rules['separate_client_identity_number'],
                'separate_client_phone' => $rules['separate_client_phone'],
                'worker_name' => $rules['worker_name'],
                'worker_passport_number' => $rules['worker_passport_number'],
            ],
            $request->messages()
        );

        $this->assertTrue($validator->errors()->has('separate_client_identity_number'));
        $this->assertTrue($validator->errors()->has('separate_client_phone'));
        $this->assertTrue($validator->errors()->has('worker_name'));
        $this->assertTrue($validator->errors()->has('worker_passport_number'));
    }

    public function test_legacy_delegation_edit_does_not_make_new_creation_fields_required(): void
    {
        $request = StoreIngazDelegationRequest::create('/admin/ingaz/ingaz-delegations/1', 'PUT', [
            'client_source_type' => 'separate_client',
        ]);
        $rules = $request->rules();

        $validator = Validator::make([], [
            'separate_client_identity_number' => $rules['separate_client_identity_number'],
            'separate_client_phone' => $rules['separate_client_phone'],
            'worker_name' => $rules['worker_name'],
            'worker_passport_number' => $rules['worker_passport_number'],
        ]);

        $this->assertFalse($validator->fails());
    }
}
