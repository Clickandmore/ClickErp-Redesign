<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Services\ShelterStayService;

class ShelterStayObserver
{
    public function created($model): void
    {
        $this->record($model, true);
    }

    public function updated($model): void
    {
        $this->record($model, false);
    }

    private function record($model, bool $created): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('shelter_stays')) return;
        if ($model instanceof ContractInfo) {
            if (!$created && !$model->wasChanged('is_outside_shelter')) return;
            $contract = $model->contract()->first();
            if (!$contract || $contract->status !== 'shelter') return;
            $wasInside = !$created && !(bool) $model->getOriginal('is_outside_shelter');
            app(ShelterStayService::class)->sync($contract, $wasInside, $model->outside_shelter_at, $model->outside_shelter_reason,
                $created ? $model->maid_date_back : null);
        } elseif ($model instanceof Contract && $model->wasChanged('status')) {
            $wasInside = $model->getOriginal('status') === 'shelter' && $model->info && !$model->info->is_outside_shelter;
            app(ShelterStayService::class)->sync($model, (bool) $wasInside);
        }
    }
}
