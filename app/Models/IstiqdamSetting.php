<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IstiqdamSetting extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $fillable = [
        'establishment_name',
        'client_id',
        'client_secret',
        'api_key',
        'updated_by',
    ];

    public function setClientSecretAttribute(?string $value): void
    {
        $this->attributes['client_secret'] = $value === null || $value === '' ? null : Crypt::encryptString($value);
    }

    public function getClientSecretAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value === null || $value === '' ? null : Crypt::encryptString($value);
    }

    public function getApiKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
