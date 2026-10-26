<?php

namespace App\Models\Hourly;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

class HourlyDriver extends Authenticatable implements JWTSubject
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $hidden = ['password', 'auth_version'];
    protected $casts = ['work_schedule' => 'array', 'is_active' => 'boolean', 'account_enabled' => 'boolean', 'vehicle_capacity' => 'integer', 'auth_version' => 'integer'];

    public const DAYS = ['sunday' => 'الأحد', 'monday' => 'الاثنين', 'tuesday' => 'الثلاثاء', 'wednesday' => 'الأربعاء', 'thursday' => 'الخميس', 'friday' => 'الجمعة', 'saturday' => 'السبت'];
    public const DATES = ['birth_date' => 'تاريخ الميلاد', 'work_start_date' => 'تاريخ بدء العمل', 'insurance_expiry_date' => 'تاريخ انتهاء التأمين', 'contract_start_date' => 'تاريخ بداية العقد', 'contract_end_date' => 'تاريخ نهاية العقد', 'replacement_expiry_date' => 'تاريخ انتهاء الاستبدال', 'residency_expiry_date' => 'تاريخ انتهاء الإقامة', 'passport_expiry_date' => 'تاريخ انتهاء جواز السفر', 'entry_date' => 'تاريخ الدخول'];

    public const EXPIRY_DATES = ['residency_expiry_date', 'passport_expiry_date', 'insurance_expiry_date', 'contract_end_date', 'replacement_expiry_date'];

    /** Labels of documents that expired or expire within $days, keyed by field, with the remaining days as value. */
    public function expiringDocuments(int $days = 30): array
    {
        $result = [];
        foreach (self::EXPIRY_DATES as $field) {
            if (!$this->$field) continue;
            $left = now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($this->$field)->startOfDay(), false);
            if ($left <= $days) $result[$field] = $left;
        }
        return $result;
    }

    /** وثائق أساسية لا يُحكم بسريانها دون تاريخ انتهاء. */
    public const REQUIRED_DOCUMENTS = ['residency_expiry_date' => 'الإقامة', 'passport_expiry_date' => 'الجواز'];

    /** أسماء الوثائق الأساسية التي لم يُدخل تاريخ انتهائها، مفهرسة بالحقل. */
    public function missingDocuments(): array
    {
        return array_filter(self::REQUIRED_DOCUMENTS, fn ($label, $field) => !$this->$field, ARRAY_FILTER_USE_BOTH);
    }

    /** Valid through the expiry date; assignment is checked against the actual task date. */
    public function residencyAssignmentReason(?\Carbon\CarbonInterface $at = null): ?string
    {
        if (!$this->residency_expiry_date) return null;
        $expiry = \Illuminate\Support\Carbon::parse($this->residency_expiry_date)->startOfDay();
        $task = \Illuminate\Support\Carbon::parse($at ?? now())->startOfDay();
        if ($expiry->gte($task)) return null;
        return __('hourly_driver_assignment.residency_blocked', [
            'name'=>$this->name, 'expiry'=>$expiry->format('Y-m-d'), 'task'=>$task->format('Y-m-d'),
        ]);
    }
    public function workingDays(): array
    {
        return array_keys(array_filter((array) $this->work_schedule, fn ($day) => ($day['mode'] ?? 'off') !== 'off'));
    }

    public function canLoginVia(string $channel): bool
    {
        return !$this->trashed() && $this->is_active && $this->account_enabled
            && in_array($this->login_access, [$channel, 'both'], true);
    }

    public function teamMemberships()
    {
        return $this->hasMany(HourlyTeamMember::class, 'member_id')->where('member_type', 'driver')->whereNull('left_at');
    }

    public function getJWTIdentifier() { return $this->getKey(); }
    public function getJWTCustomClaims(): array { return ['driver_version' => $this->auth_version]; }
}
