<?php

namespace App\Models;

use App\Models\ingaz\User;
use App\Support\CompanyContractParty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CompanyProfile extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['license_expiry_date' => 'date', 'account_id' => 'integer'];

    public static function current(): ?self
    {
        return Schema::hasTable('company_profiles') ? static::query()->first() : null;
    }

    public function party(?int $historicalAccountId = null): User
    {
        // Read-only compatibility view for existing contract printing/export APIs.
        // It has no user ID and cannot create or modify a customer record.
        return new CompanyContractParty([
            'company_profile_reference' => $this->id,
            'name' => app()->getLocale() === 'en' && $this->name_en ? $this->name_en : $this->name,
            'phone' => $this->phone, 'phone_code' => '', 'phone2' => $this->phone2,
            'email' => $this->email, 'address' => $this->address, 'city' => $this->city,
            'client_type' => 'company', 'tax_number' => $this->tax_number,
            'national_num' => $this->registration_number, 'license_number' => $this->license_number,
            'license_expiry_date' => $this->license_expiry_date,
            'account_id' => $historicalAccountId ?: $this->account_id,
        ]);
    }
}
