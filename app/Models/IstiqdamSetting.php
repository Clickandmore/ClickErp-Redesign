<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IstiqdamSetting extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $fillable = [
        'establishment_name',
        'environment',
        'base_url',
        'oauth_url',
        'client_id',
        'client_secret',
        'api_key',
        'scope',
        'timeout',
        'connect_timeout',
        'token_cache_ttl',
        'logging_enabled',
        'updated_by',
    ];

    protected $casts = [
        'timeout' => 'integer',
        'connect_timeout' => 'integer',
        'token_cache_ttl' => 'integer',
        'logging_enabled' => 'boolean',
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
