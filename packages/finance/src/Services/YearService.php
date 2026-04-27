<?php

namespace Nami\Finance\Services;

use Carbon\Carbon;
use Nami\Finance\MainService;
use Nami\Finance\Models\FnYear;

class YearService extends MainService
{
    public function __construct(FnYear $model)
    {
        $this->model = $model;
        $this->fileFolder = 'fn_year/';
    }

    public function isDateRangeOverlap($dataInsert)
    {
        $from_date = Carbon::parse($dataInsert['from_date']);
        $to_date = Carbon::parse($dataInsert['to_date']);
        $exists = $this->model->whereYear('from_date', $from_date->year)
            ->where(function ($query) use ($from_date, $to_date) {
                $query->whereBetween('from_date', [$from_date, $to_date])
                    ->orWhereBetween('to_date', [$from_date, $to_date])
                    ->orWhere(function ($query) use ($from_date, $to_date) {
                        $query->where('from_date', '<', $from_date)
                            ->where('to_date', '>', $to_date);
                    });
            })->exists();
        return $exists;
    }

    public function isDateRangeOverlapForUpdate($dataInsert, $id)
    {
        $from_date = Carbon::parse($dataInsert['from_date']);
        $to_date = Carbon::parse($dataInsert['to_date']);
        $exists = $this->model->where('id', '!=', $id)
            ->whereYear('from_date', $from_date->year)
            ->where(function ($query) use ($from_date, $to_date) {
                $query->whereBetween('from_date', [$from_date, $to_date])
                    ->orWhereBetween('to_date', [$from_date, $to_date])
                    ->orWhere(function ($query) use ($from_date, $to_date) {
                        $query->where('from_date', '<', $from_date)
                            ->where('to_date', '>', $to_date);
                    });
            })->exists();

        return $exists;
    }

    public function deactivateAll()
    {
        $this->model->where('status', 1)->update(['status' => 0]);
    }

    public function getActiveYear()
    {
        return $this->model->where('status', 1)->first();
    }

    public function checkDateRange($date)
    {
        $date = Carbon::parse($date);
        $exists = $this->model->whereDate('from_date','<=', $date)
            ->whereDate('to_date','>', $date)->where('status', 1)->first();

        return $exists;
    }

}
