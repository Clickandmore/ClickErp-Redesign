<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TravelHistory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'maid_id',
        'order_id',//null
        'trip_group_code',
        'travel_type',
        'travel_with',
        'status',
        'notes',
        'arrival_airport',
        'arrival_date',
        'arrival_time',
        'flight_company',
        'flight_number',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'assignment_note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function files()
    {
        return $this->hasMany(TravelHistoryFiles::class, 'travel_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(Admin::class, 'assigned_by');
    }

    /**
     * Resolve legacy arrival values where arrival_date may already contain a time.
     */
    public function resolveArrivalDateTime(): ?Carbon
    {
        $rawDate = trim((string) $this->arrival_date);
        if ($rawDate === '') {
            return null;
        }

        try {
            $arrivalDateTime = Carbon::parse($rawDate);
        } catch (\Throwable $e) {
            return null;
        }

        $rawTime = trim((string) $this->arrival_time);
        if ($rawTime !== '') {
            try {
                $arrivalTime = Carbon::parse($rawTime);
                $arrivalDateTime->setTime(
                    $arrivalTime->hour,
                    $arrivalTime->minute,
                    $arrivalTime->second
                );
            } catch (\Throwable $e) {
                // Preserve the valid date (and any embedded time) when the
                // separate legacy time value is malformed.
            }
        }

        return $arrivalDateTime;
    }

    /**
     * Store the date and time in their dedicated fields before completing a trip.
     */
    public function normalizeArrivalDateAndTime(?Carbon $fallback = null): Carbon
    {
        $fallback = ($fallback ?: now())->copy();
        $rawDate = trim((string) $this->arrival_date);
        $rawTime = trim((string) $this->arrival_time);
        $arrivalDateTime = $this->resolveArrivalDateTime() ?: $fallback->copy();

        if ($rawDate === '') {
            $arrivalDateTime->setDate($fallback->year, $fallback->month, $fallback->day);
        }

        if ($rawTime === '' && !preg_match('/\d{1,2}:\d{2}/', $rawDate)) {
            $arrivalDateTime->setTime($fallback->hour, $fallback->minute, $fallback->second);
        }

        $this->arrival_date = $arrivalDateTime->toDateString();
        $this->arrival_time = $arrivalDateTime->format('H:i:s');

        return $arrivalDateTime;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
