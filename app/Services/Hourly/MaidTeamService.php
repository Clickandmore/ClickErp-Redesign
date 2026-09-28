<?php
namespace App\Services\Hourly;
use App\Models\ingaz\Maid;
use App\Models\Hourly\{HourlyTeam, HourlyTeamMember};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaidTeamService
{
    public function sync(Maid $maid, ?int $primary, array $secondary): void
    {
        $secondary=array_unique(array_map('intval',$secondary));
        if ($primary && in_array($primary,$secondary,true)) throw ValidationException::withMessages(['secondary_team_ids'=>'لا يمكن اختيار الفريق نفسه كأساسي وثانوي.']);
        $wanted=array_fill_keys($secondary,'secondary');
        if ($primary) $wanted[$primary]='primary';
        DB::transaction(function () use ($maid,$wanted) {
            Maid::whereKey($maid->id)->lockForUpdate()->firstOrFail();
            $current=$maid->hourlyTeamMemberships()->get();
            foreach ($wanted as $id=>$role) {
                $team=HourlyTeam::whereKey($id)->lockForUpdate()->first();
                if (!$team || (!$team->is_active && !$current->contains('hourly_team_id',$id))) throw ValidationException::withMessages(['primary_team_id'=>'اختر فريقًا متاحًا.']);
            }
            foreach ($current as $member) {
                if (($wanted[$member->hourly_team_id] ?? null)===$member->role) unset($wanted[$member->hourly_team_id]);
                else $member->update(['left_at'=>now()->toDateString()]);
            }
            foreach ($wanted as $id=>$role) HourlyTeamMember::create(['hourly_team_id'=>$id,'member_type'=>'maid','member_id'=>$maid->id,'role'=>$role,'joined_at'=>now()->toDateString(),'is_active'=>true]);
        });
    }

    public function assign(Maid $maid, HourlyTeam $team, string $role): void
    {
        DB::transaction(function () use ($maid,$team,$role) {
            Maid::whereKey($maid->id)->lockForUpdate()->firstOrFail();
            $current=$maid->hourlyTeamMemberships()->get();
            $primary=$current->firstWhere('role','primary')?->hourly_team_id;
            $secondary=$current->where('role','secondary')->pluck('hourly_team_id')->all();
            if ($role==='primary') $this->sync($maid,$team->id,array_diff($secondary,[$team->id]));
            else $this->sync($maid,$primary==$team->id ? null : $primary,array_merge($secondary,[$team->id]));
        });
    }
}
