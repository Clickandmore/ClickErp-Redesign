<?php

namespace App\Models\Hourly;

use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Model;

/**
 * عضوية في فريق: عاملة أو سائق. السائق يُربط لاحقًا حين يُنشأ جدوله.
 */
class HourlyTeamMember extends Model
{
    public const TYPE_MAID = 'maid';
    public const TYPE_DRIVER = 'driver';

    public const ROLE_PRIMARY = 'primary';
    public const ROLE_SECONDARY = 'secondary';

    protected $table = 'hourly_team_members';

    protected $fillable = [
        'hourly_team_id', 'member_type', 'member_id', 'role', 'is_leader',
        'joined_at', 'left_at', 'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_leader' => 'boolean',
        'is_active' => 'boolean',
        'joined_at' => 'date',
        'left_at' => 'date',
    ];

    public function team()
    {
        return $this->belongsTo(HourlyTeam::class, 'hourly_team_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'member_id');
    }

    public function driver()
    {
        return $this->belongsTo(HourlyDriver::class, 'member_id');
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('left_at');
    }
}
