<?php

namespace App\Models;

use App\Models\Country;
use App\Models\ingaz\Nationality;
use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngazDelegation extends Model
{
    use AssignsBranch, HasFactory;

    public const STATUS_UNDER_PROCESS = 'under_process';
    public const STATUS_EXECUTED = 'executed';

    protected $fillable = [
        'delegation_date_gregorian',
        'delegation_date_hijri',
        'nationality_id',
        'country_id',
        'profession_id',
        'user_id',
        'branch_id',
        'maid_id',
        'marketer_id',
        'client_source_type',
        'visa_type',
        'worker_name',
        'worker_passport_number',
        'worker_passport_issue_date',
        'visa_number',
        'visa_date',
        'worker_religion',
        'worker_experience',
        'external_office_name',
        'external_office_license_number',
        'external_office_address',
        'external_office_phone',
        'status',
        'ingaz_number',
        'msaned_contract_number',
        'notes',
        'attachments',
    ];

    protected $casts = [
        'delegation_date_gregorian' => 'date',
        'worker_passport_issue_date' => 'date',
        'visa_date' => 'date',
        'attachments' => 'array',
        'branch_id' => 'integer',
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_UNDER_PROCESS => 'تحت المعالجة',
            self::STATUS_EXECUTED => 'تم تنفيذ التفويض',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function profession()
    {
        return $this->belongsTo(JobTitle::class, 'profession_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function marketer()
    {
        return $this->belongsTo(Admin::class, 'marketer_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'ingaz_delegation_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromUser($this->user_id)
            ?? $this->resolveBranchIdFromMaid($this->maid_id)
            ?? $this->resolveBranchIdFromAdmin($this->marketer_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
