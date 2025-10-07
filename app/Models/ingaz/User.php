<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use App\Models\Message;
use Spatie\Activitylog\Models\Activity;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, LogsActivity, HasRoles;

    protected $table = 'users';
    protected $guarded = [];


    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'user_id');
    }

    public function nationality()
    {
        return $this->belongsTo(\App\Models\Country::class, 'nationality_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    // Mahmoud Amer's code
     public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }
    public function activities()
    {
        return $this->hasMany(Activity::class, 'subject_id');
    }
}
