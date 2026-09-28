<?php
namespace App\Models\Hourly;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

class HourlyMaidAccount extends Authenticatable implements JWTSubject
{
    use SoftDeletes;
    protected $table = 'maids';
    protected $guarded = ['*'];
    protected $hidden = ['hourly_password','hourly_auth_version'];
    protected $casts = ['hourly_settings'=>'array','hourly_auth_version'=>'integer'];
    public function getAuthPassword() { return $this->hourly_password; }
    public function canLoginVia(string $channel): bool {
        return !$this->trashed() && $this->rental_mode === 'hourly'
            && !empty($this->hourly_settings['account_enabled'])
            && in_array($this->hourly_settings['login_access'] ?? '',[$channel,'both'],true);
    }
    public function getJWTIdentifier() { return $this->getKey(); }
    public function getJWTCustomClaims(): array { return ['maid_version'=>$this->hourly_auth_version]; }
}
