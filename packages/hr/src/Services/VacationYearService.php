<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\VacationYear;
use Carbon\Carbon;

class VacationYearService extends MainService
{
    public function __construct(VacationYear $model)
    {
        $this->model = $model;
        $this->fileFolder = 'hr_year/';
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

    public function isOverTheYear($dataInsert)
    {
        $year = $this->getYearByDate($dataInsert['from_date']);
        if (!$year){
            return 405;
        }
        $activeYearStart = Carbon::parse($year->from_date);
        $activeYearEnd = Carbon::parse($year->to_date);

        // Parse the vacation dates
        $from_date = Carbon::parse($dataInsert['from_date']);
        $to_date = Carbon::parse($dataInsert['to_date']);
        if ($year->id != $this->getActiveYear()->id){
            return 406;
        }
        // Check if both from_date and to_date are within the active year range
        if ($from_date->year !== $activeYearStart->year || $to_date->year !== $activeYearEnd->year) {
            return 405;  // Vacation is not fully within the active year
        }

        return 200;  // Vacation is within the same active year
    }

    public function getYearByDate($start_date)
    {
        // Parse the start date
        $startDate = Carbon::parse($start_date);

        // Query the active year table (assuming the table stores 'from_date' and 'to_date')
        $activeYear = $this->model->where('from_date', '<=', $startDate)
            ->where('to_date', '>=', $startDate)
            ->first();

        return $activeYear;
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
