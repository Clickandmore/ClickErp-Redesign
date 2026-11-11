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
use Illuminate\Support\Facades\Schema;

use App\Models\Concerns\BranchScoped;

class IngazDelegation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, HasFactory, BranchScoped;

    public const STATUS_UNDER_PROCESS = 'under_process';
    public const STATUS_EXECUTED = 'executed';
    public const STATUS_CANCELLED = 'cancelled';

    private static ?array $statusOptionsCache = null;

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
        'active_ingaz_number_key',
        'msaned_contract_number',
        'active_msaned_contract_number_key',
        'active_visa_number_key',
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

    public function hasFinancialLinks(): bool
    {
        // Include all invoices, even fully credited ones, across branch scopes.
        foreach (['invoices', 'recruitment_invoice_credit_notes', 'musaned_transfer_items', 'commission_batch_items'] as $table) {
            if (\Illuminate\Support\Facades\DB::table($table)
                ->where('ingaz_delegation_id', $this->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    public function ensureCanDelete(): void
    {
        if ($this->hasFinancialLinks()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'delegation' => 'لا يمكن حذف التفويض لوجود ارتباطات مالية عليه.',
            ]);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (self $delegation) {
            $delegation->ensureCanDelete();
        });

        static::creating(function (self $delegation) {
            $delegation->syncActiveDuplicateKeys();
        });

        static::updating(function (self $delegation) {
            if (
                $delegation->isDirty('status')
                || $delegation->isDirty('ingaz_number')
                || $delegation->isDirty('msaned_contract_number')
                || $delegation->isDirty('visa_number')
            ) {
                $delegation->syncActiveDuplicateKeys();
            }
        });
    }

    public function syncActiveDuplicateKeys(): void
    {
        $active = $this->status !== self::STATUS_CANCELLED;
        $this->active_ingaz_number_key = $active ? self::duplicateKey($this->ingaz_number) : null;
        $this->active_msaned_contract_number_key = $active ? self::duplicateKey($this->msaned_contract_number) : null;
        $this->active_visa_number_key = $active ? self::duplicateKey($this->visa_number) : null;
    }

    public static function duplicateKey(?string $value): ?string
    {
        $normalized = mb_strtolower(trim((string) $value));

        if ($normalized === '' || preg_match('/^0+$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    public static function statusOptions(?string $includeStatus = null): array
    {
        if (!Schema::hasTable('ingaz_delegation_stages')) {
            return [
                self::STATUS_UNDER_PROCESS => __('delegations.under_process'),
                self::STATUS_EXECUTED => __('delegations.executed'),
                self::STATUS_CANCELLED => __('delegations.cancelled'),
            ];
        }

        $locale = app()->getLocale();
        self::$statusOptionsCache[$locale] ??= IngazDelegationStage::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (IngazDelegationStage $stage) => [
                $stage->key => ['label' => $stage->displayName(), 'active' => $stage->is_active],
            ])->all();

        return collect(self::$statusOptionsCache[$locale])
            ->filter(fn (array $stage, string $key) => $stage['active'] || $key === $includeStatus)
            ->map(fn (array $stage) => $stage['label'])
            ->all();
    }

    public static function clearStatusOptionsCache(): void
    {
        self::$statusOptionsCache = null;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions($this->status)[$this->status] ?? $this->status;
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
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
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
        return $this->hasOne(Invoice::class, 'ingaz_delegation_id')->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->recruitmentRevenue()
        );
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'ingaz_delegation_id');
    }

    public function blockingInvoices()
    {
        return $this->invoices()->blockingReplacement();
    }

    public function creditNotes()
    {
        return $this->hasMany(RecruitmentInvoiceCreditNote::class, 'ingaz_delegation_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromCurrentAdmin()
            ?? $this->resolveBranchIdFromUser($this->user_id)
            ?? $this->resolveBranchIdFromMaid($this->maid_id)
            ?? $this->resolveBranchIdFromAdmin($this->marketer_id);
    }
}
