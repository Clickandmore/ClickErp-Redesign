<?php

namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyDriver, HourlyTeam, HourlyTeamMember};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverTeamService
{
    public function sync(HourlyDriver $driver, ?int $primary, array $secondary): void
    {
        $secondary = array_values(array_unique(array_map('intval', $secondary)));
        if ($primary && in_array($primary, $secondary, true)) {
            throw ValidationException::withMessages(['secondary_team_ids' => 'لا يمكن اختيار الفريق نفسه كأساسي وثانوي.']);
        }
        $wanted = array_fill_keys($secondary, 'secondary');
        if ($primary) $wanted[$primary] = 'primary';
        DB::transaction(function () use ($driver, $wanted) {
            HourlyDriver::whereKey($driver->id)->lockForUpdate()->firstOrFail();
            $current = $driver->teamMemberships()->get();
            foreach ($wanted as $teamId => $role) {
                $team = HourlyTeam::whereKey($teamId)->lockForUpdate()->first();
                if (!$team || (!$team->is_active && !$current->contains('hourly_team_id', $teamId))) {
                    throw ValidationException::withMessages(['primary_team_id' => 'اختر فريقًا متاحًا.']);
                }
            }
            foreach ($current as $member) {
                if (($wanted[$member->hourly_team_id] ?? null) !== $member->role) {
                    $member->update(['left_at' => now()->toDateString()]);
                } else {
                    unset($wanted[$member->hourly_team_id]);
                }
            }
            foreach ($wanted as $teamId => $role) {
                HourlyTeamMember::create(['hourly_team_id'=>$teamId, 'member_type'=>'driver', 'member_id'=>$driver->id, 'role'=>$role, 'is_active'=>true, 'joined_at'=>now()->toDateString(), 'created_by'=>auth('admin')->id()]);
            }
        });
    }

    public function assign(HourlyDriver $driver, HourlyTeam $team, string $role): void
    {
        DB::transaction(function () use ($driver, $team, $role) {
            HourlyDriver::whereKey($driver->id)->lockForUpdate()->firstOrFail();
            $members = $driver->teamMemberships()->get();
            $primary = $members->firstWhere('role', 'primary');
            $secondary = $members->where('role', 'secondary')->pluck('hourly_team_id')->map(fn ($id) => (int) $id)->all();
            if ($role === 'primary') {
                $secondary = array_values(array_diff($secondary, [$team->id]));
                $this->sync($driver, $team->id, $secondary);
            } else {
                $this->sync($driver, $primary && $primary->hourly_team_id != $team->id ? $primary->hourly_team_id : null, array_merge($secondary, [$team->id]));
            }
        });
    }
}
