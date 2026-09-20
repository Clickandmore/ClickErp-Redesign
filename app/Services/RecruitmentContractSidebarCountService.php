<?php

namespace App\Services;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RecruitmentContractSidebarCountService
{
    public function counts(Request $request): array
    {
        $counts = [
            'new' => 0,
            'msaned' => 0,
            'tafwed' => 0,
            'taffez' => 0,
            'tazker' => 0,
            'under_warranty' => 0,
            'back' => 0,
            'arrival' => 0,
            'closed' => 0,
            'completed' => 0,
            'pending_maid_selection' => 0,
            'all' => 0,
            'are_valid' => 0,
            'external_office_dispatch_pending' => 0,
        ];

        if (!Schema::hasTable('contracts')) {
            return $counts;
        }

        $baseQuery = app(BranchAccessService::class)
            ->applyToQuery(Contract::query(), $request, 'contracts');

        $counts['under_warranty'] = (clone $baseQuery)
            ->where('status', ContractStatusEnum::Arrival->value)
            ->whereNotNull('warranty_end_date')
            ->whereDate('warranty_end_date', '>=', now()->toDateString())
            ->count();
        $counts['back'] = (clone $baseQuery)->where('status', ContractStatusEnum::Back->value)->count();
        $counts['arrival'] = (clone $baseQuery)->where('status', ContractStatusEnum::Arrival->value)->count();
        $counts['closed'] = (clone $baseQuery)->where('status', ContractStatusEnum::Closed->value)->count();
        $counts['pending_maid_selection'] = (clone $baseQuery)
            ->where('status', ContractStatusEnum::PendingMaidSelection->value)
            ->count();
        $counts['are_valid'] = (clone $baseQuery)
            ->whereIn('type', ['recruitment', 'maid'])
            ->whereIn('status', [
                ContractStatusEnum::PendingMaidSelection->value,
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Sari->value,
            ])
            ->whereDoesntHave('travelHistory', function ($travelQuery) {
                $travelQuery->where('status', 'completed');
            })
            ->count();
        $counts['completed'] = (clone $baseQuery)
            ->where('status', ContractStatusEnum::Completed->value)
            ->count();

        if (Schema::hasTable('contract_external_office_dispatches')) {
            $counts['external_office_dispatch_pending'] = (clone $baseQuery)
                ->whereIn('type', ['recruitment', 'maid'])
                ->whereIn('status', [
                    ContractStatusEnum::PendingMaidSelection->value,
                    ContractStatusEnum::New->value,
                    ContractStatusEnum::Msaned->value,
                    ContractStatusEnum::Tafwed->value,
                    ContractStatusEnum::Taffez->value,
                    ContractStatusEnum::Tazker->value,
                ])
                ->whereDoesntHave('externalOfficeDispatch')
                ->count();
        }

        return $counts;
    }
}
