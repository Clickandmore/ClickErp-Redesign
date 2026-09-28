<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver,HourlyTeam};

class TeamLeaderChoices
{
    public function query(?int $teamId,bool $allTeams=false)
    {
        return HourlyDriver::where('is_active',true)->whereIn('id',function($query)use($teamId,$allTeams){
            $query->select('driver_id')->from('hourly_team_driver_leaders')
                ->whereIn('hourly_team_id',HourlyTeam::where('is_active',true)->select('id'));
            if(!$allTeams)$query->where('hourly_team_id',$teamId);
        });
    }
}
