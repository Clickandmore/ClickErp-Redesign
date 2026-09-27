<?php

namespace App\Services;

use App\Models\ingaz\User as ClientUser;

class ShelterCompanyClientResolver
{
    public function applyCompanyIdentityFilter($query): void
    {
        $companyName = trim((string) documentSetting(
            'document_company_name',
            documentSetting('website_name', config('app.name'))
        ));

        $names = array_filter([
            $companyName !== '' ? $companyName : config('app.name'),
            'شركة أسس الإنجاز للاستقدام',
            'شركة أسس الانجاز للاستقدام',
            'شركة أسس الإنجاز',
            'شركة أسس الانجاز',
        ]);

        $query->where(function ($query) use ($names) {
            foreach ($names as $name) {
                $query->orWhere('name', 'like', '%' . trim($name) . '%');
            }
        });
    }

    public function resolve(): ClientUser
    {
        $defaultCompanyName = config('app.name');
        $configuredCompanyName = trim((string) documentSetting(
            'document_company_name',
            documentSetting('website_name', $defaultCompanyName)
        ));
        $companyName = $configuredCompanyName !== '' ? $configuredCompanyName : $defaultCompanyName;
        $companyPhone = $this->resolvePhone($companyName, (string) documentSetting('phone', '0112345678'));
        $companyEmail = documentSetting('email');
        $companyQuery = ClientUser::query();
        $this->applyCompanyIdentityFilter($companyQuery);

        $companyClient = $companyQuery
            ->orWhere('phone', $companyPhone)
            ->first();

        if ($companyClient) {
            $updatePayload = [];

            if (trim((string) $companyClient->name) !== $companyName) {
                $updatePayload['name'] = $companyName;
            }

            if (blank($companyClient->phone)) {
                $updatePayload['phone'] = $companyPhone;
            }

            if (blank($companyClient->phone_code)) {
                $updatePayload['phone_code'] = '+966';
            }

            if (blank($companyClient->email) && filled($companyEmail)) {
                $updatePayload['email'] = $companyEmail;
            }

            if ($updatePayload !== []) {
                $companyClient->update($updatePayload);
            }

            return $companyClient;
        }

        return ClientUser::create([
            'name' => $companyName,
            'phone' => $companyPhone,
            'phone_code' => '+966',
            'email' => filled($companyEmail) ? $companyEmail : null,
            'nationality_id' => 1,
            'password' => bcrypt('password123'),
            'status' => 1,
        ]);
    }

    private function resolvePhone(string $companyName, string $preferredPhone): string
    {
        $phone = preg_replace('/\D+/', '', $preferredPhone) ?: '';

        if ($phone !== '') {
            $existingPhoneOwner = ClientUser::query()
                ->where('phone', $phone)
                ->where(function ($query) use ($companyName) {
                    $query->where('name', $companyName)
                        ->orWhere('name', 'like', '%' . $companyName . '%');
                })
                ->exists();

            $phoneInUse = ClientUser::query()
                ->where('phone', $phone)
                ->exists();

            if ($existingPhoneOwner || ! $phoneInUse) {
                return $phone;
            }
        }

        $fallbackPhone = substr('9' . str_pad((string) abs(crc32($companyName)), 9, '0', STR_PAD_LEFT), 0, 10);

        while (ClientUser::query()->where('phone', $fallbackPhone)->exists()) {
            $fallbackPhone = substr('9' . str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT), 0, 10);
        }

        return $fallbackPhone;
    }
}
