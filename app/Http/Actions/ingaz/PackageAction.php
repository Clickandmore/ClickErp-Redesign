<?php

namespace App\Http\Actions\ingaz;

use App\Http\Actions\MainAction;
use App\Models\ingaz\Package;

class PackageAction extends MainAction
{
    public function __construct(Package $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Package/';
    }

    public function updatePackage($id, $data)
    {
        $row = $this->find($id);
        return $row->update($this->prepareData($data));
    }

    public function store($data)
    {
        return $this->model->create($this->prepareData($data));
    }

    public function get()
    {
        return $this->model->ordered()->get();
    }

    private function prepareData(array $data): array
    {
        $data = $this->clearRequesrt($data);

        $data['nationality_id'] = $data['nationality_id'] ?? null;
        $data['rental_type'] = $data['rental_type'] ?? 'monthly';
        $data['duration_unit'] = $data['duration_unit'] ?? 'month';
        $data['duration_count'] = max(1, (int) ($data['duration_count'] ?? 1));
        $data['days_count'] = $this->resolveDaysCount($data);
        $data['sort_order'] = max(0, (int) ($data['sort_order'] ?? 0));
        $data['price_after_discount'] = $data['price_after_discount'] ?? $data['original_price'];
        $data['price_with_tax'] = $data['price_with_tax'] ?? round(((float) $data['price_after_discount']) * 1.15, 2);
        $data['is_show'] = array_key_exists('is_show', $data) ? (bool) $data['is_show'] : true;

        return $data;
    }

    private function resolveDaysCount(array $data): int
    {
        $durationCount = max(1, (int) ($data['duration_count'] ?? 1));

        return match ($data['duration_unit'] ?? 'month') {
            'hour' => 1,
            'day' => $durationCount,
            'week' => $durationCount * 7,
            'year' => $durationCount * 365,
            default => $durationCount * 30,
        };
    }

}
