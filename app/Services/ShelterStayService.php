<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShelterStayService
{
    public function sync(Contract $contract, bool $wasInside, $exitAt = null, ?string $reason = null, $entryAt = null): void
    {
        if (!Schema::hasTable('shelter_stays')) return;

        DB::transaction(function () use ($contract, $wasInside, $exitAt, $reason, $entryAt) {
            // Serialize movements for the same shelter contract.
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            $inside = $contract->status === 'shelter' && $contract->info && !$contract->info->is_outside_shelter;
            $open = DB::table('shelter_stays')->where('contract_id', $contract->id)->whereNull('exited_at')->first();
            $hasHistory = DB::table('shelter_stays')->where('contract_id', $contract->id)->exists();

            if (!$open && !$hasHistory && $wasInside) {
                $id = DB::table('shelter_stays')->insertGetId([
                    'contract_id' => $contract->id,
                    'entered_at' => $this->legacyEntry($contract),
                    'estimated' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $open = DB::table('shelter_stays')->find($id);
            }

            if (!$inside && $open) {
                $end = Carbon::parse($exitAt ?: now());
                if ($open->entered_at && $end->lt(Carbon::parse($open->entered_at))) $end = Carbon::parse($open->entered_at);
                DB::table('shelter_stays')->where('id', $open->id)->update([
                    'exited_at' => $end, 'exit_reason' => $reason ?: $contract->status, 'updated_at' => now(),
                ]);
            } elseif ($inside && !$open) {
                DB::table('shelter_stays')->insert([
                    'contract_id' => $contract->id, 'entered_at' => $entryAt ?: now(),
                    'estimated' => false, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    private function legacyEntry(Contract $contract): ?string
    {
        // This is only a baseline; it cannot reconstruct earlier unrecorded absences.
        $date = $contract->info?->maid_date_back ?: $contract->latestShelterLog?->date;
        return $date ? Carbon::parse($date)->toDateTimeString() : null;
    }

    public function summary(Contract $contract): array
    {
        $rows = Schema::hasTable('shelter_stays')
            ? DB::table('shelter_stays')->where('contract_id', $contract->id)->orderBy('id')->get()
            : collect();
        if ($rows->isEmpty() && $contract->info) {
            $entry = $this->legacyEntry($contract);
            if ($entry) $rows->push((object) [
                'entered_at' => $entry,
                'exited_at' => $contract->info->is_outside_shelter ? $contract->info->outside_shelter_at : null,
                'exit_reason' => $contract->info->outside_shelter_reason,
                'estimated' => true,
            ]);
        }
        $periods = $rows->map(function ($row) use ($contract) {
            $start = $row->entered_at ? Carbon::parse($row->entered_at)->startOfDay() : null;
            $end = $row->exited_at ? Carbon::parse($row->exited_at)->startOfDay() : null;
            $ongoing = !$end && $contract->status === 'shelter' && !$contract->info?->is_outside_shelter;
            $until = $end ?: ($ongoing ? today() : null);
            $days = $start && $until && $until->gte($start) ? $start->diffInDays($until) : null;
            return ['entry' => $start, 'exit' => $end, 'ongoing' => $ongoing,
                'days' => $days, 'estimated' => (bool) $row->estimated];
        });
        return ['periods' => $periods, 'total_days' => $periods->sum('days'),
            'incomplete' => $periods->isEmpty() || $periods->contains(fn ($row) => $row['estimated'] || $row['days'] === null)];
    }
}
