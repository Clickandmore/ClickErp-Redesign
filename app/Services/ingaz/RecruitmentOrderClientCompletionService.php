<?php

namespace App\Services\ingaz;

use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecruitmentOrderClientCompletionService
{
    public function saveDeliveryCity(User $user, Request $request): void
    {
        $city = $request->filled('order_id')
            ? $request->input('client_city')
            : ($request->input('new_client_city') ?: $request->input('client_city'));
        if (!is_string($city) || trim($city) === '') {
            return;
        }
        $city = trim($city);
        Validator::make(['city' => $city], ['city' => 'string|max:255'])->validate();
        if ($user->city !== $city) {
            $user->update(['city' => $city]);
        }
    }

    public function complete(User $user, Request $request): void
    {
        $accountType = $user->client_type ?: $request->input('account_client_type', 'person');
        $fields = [
            'name' => 'client_name', 'phone' => 'client_phone',
            'email' => 'client_email', 'nationality_id' => 'client_nationality_id',
            'phone2' => 'client_phone2', 'address' => 'client_address',
            'city' => 'client_city',
        ];
        $fields += $accountType === 'company'
            ? ['tax_number' => 'client_national_num', 'license_number' => 'client_license_number']
            : [
                'national_num' => 'client_national_num', 'birthdate' => 'client_birthdate',
                'rooms_num' => 'client_beds_num', 'family_num' => 'client_family_num',
                'children' => 'client_children', 'job' => 'client_job',
            ];

        $payload = [];
        foreach ($fields as $column => $input) {
            // Completing an order must not replace shared, previously recorded client data.
            if (blank($user->getAttribute($column)) && $request->filled($input)) {
                $payload[$column] = $request->input($input);
            }
        }

        $rules = [
            'name' => 'string|max:255', 'phone' => ['string', 'max:50', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => 'email|max:255', 'nationality_id' => 'integer|exists:countries,id',
            'phone2' => 'string|max:50', 'address' => 'string|max:255', 'city' => 'string|max:255',
            'national_num' => ['string', 'max:50', Rule::unique('users', 'national_num')->ignore($user->id)],
            'tax_number' => ['string', 'max:50', Rule::unique('users', 'tax_number')->ignore($user->id)],
            'license_number' => 'string|max:100', 'birthdate' => 'date',
            'rooms_num' => 'integer|min:0', 'family_num' => 'integer|min:0',
            'children' => 'integer|min:0|max:50', 'job' => 'string|max:255',
        ];
        $validator = Validator::make($payload, $rules);
        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $column => $messages) {
                $errors[$fields[$column]] = $messages;
            }
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        if ($payload !== []) {
            if (blank($user->client_type)) {
                $payload['client_type'] = $accountType;
            }
            $user->fill($payload)->save();
        }
    }
}
