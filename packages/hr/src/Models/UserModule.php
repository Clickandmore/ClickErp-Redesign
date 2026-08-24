<?php

namespace Nami\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserModule extends HrModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'module',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
