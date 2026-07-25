<?php

namespace App\Models\ingaz;

use App\Models\Branch;
use App\Support\SystemModuleManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use App\Models\Message;
use Spatie\Activitylog\Models\Activity;
use App\Models\RentalContract;
use App\Models\SponsorshipTransfer;
use App\Models\Contract;
use App\Models\ingaz\Maid;
use App\Models\auth\Admin;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ClientCategory;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, LogsActivity, HasRoles;

    protected $table = 'users';
    protected $guarded = [];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    public function clientNumber()
    {
        return $this->arabia_client_number ?: $this->getKey();
    }

    public function primaryPhoneValue(): string
    {
        $sourcePhone = trim((string) ($this->source_phone ?? ''));
        if ($sourcePhone !== '') {
            return $sourcePhone;
        }

        $phone = trim((string) $this->phone);
        if ($phone === ''
            || str_starts_with($phone, 'arabia-client-')
            || str_starts_with($phone, 'data-import-')) {
            return '';
        }

        return trim((string) $this->phone_code . $phone);
    }

    public function secondaryPhoneValue(): string
    {
        return trim((string) (($this->source_phone2 ?? null) ?: $this->phone2));
    }

    public function isCompanyClient(): bool
    {
        return (string) $this->client_type === 'company';
    }

    public function documentNumberLabel(): string
    {
        return $this->isCompanyClient()
            ? __('ingaz.tax_number')
            : __('ingaz.national_num');
    }

    public function documentNumber(): ?string
    {
        $value = $this->isCompanyClient()
            ? $this->tax_number
            : $this->national_num;

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function searchableDocumentNumber(): ?string
    {
        return $this->documentNumber() ?: ($this->national_num ?: $this->tax_number);
    }

    public function clientTypeAwarePayload(array $payload, $documentNumber = null): array
    {
        if ($this->isCompanyClient()) {
            foreach (['national_num', 'birthdate', 'marital_status', 'rooms_num', 'family_num', 'children', 'job', 'profession', 'monthly_income'] as $field) {
                unset($payload[$field]);
            }

            if ($documentNumber !== null && trim((string) $documentNumber) !== '') {
                $payload['tax_number'] = trim((string) $documentNumber);
            }

            return $payload;
        }

        if ($documentNumber !== null) {
            $payload['national_num'] = trim((string) $documentNumber) !== ''
                ? trim((string) $documentNumber)
                : $this->national_num;
        }

        return $payload;
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (!$user->branchesFeatureAvailable() || !empty($user->branch_id)) {
                return;
            }

            $branchId = $user->resolveDefaultBranchId();

            if ($branchId) {
                $user->branch_id = $branchId;
            }
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'user_id');
    }

    public function nationality()
    {
        return $this->belongsTo(\App\Models\Country::class, 'nationality_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function financeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function financeCategory(): BelongsTo
    {
        return $this->belongsTo(ClientCategory::class, 'client_category_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    // Mahmoud Amer's code
    public function messages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }
    public function activities()
    {
        return $this->hasMany(Activity::class, 'subject_id');
    }

    public function rentalContracts()
    {
        return $this->hasMany(RentalContract::class, 'user_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'user_id');
    }

    public function sponsorshipTransfersAsNewSponsor()
    {
        return $this->hasMany(SponsorshipTransfer::class, 'new_sponsor_id');
    }

    public function sponsorshipTransfersAsOldSponsor()
    {
        return $this->hasMany(SponsorshipTransfer::class, 'old_sponsor_id');
    }

    public function clientAttachments()
    {
        return $this->hasMany(ClientAttachment::class, 'user_id');
    }

    public function importDetails()
    {
        return $this->hasMany(ClientImportDetail::class, 'user_id')->orderBy('sort_order');
    }

    private function branchesFeatureAvailable(): bool
    {
        return SystemModuleManager::isEnabled('branches')
            && Schema::hasTable($this->getTable())
            && Schema::hasColumn($this->getTable(), 'branch_id');
    }

    private function resolveDefaultBranchId(): ?int
    {
        if (!Schema::hasTable('branches')) {
            return null;
        }

        $branch = Branch::query()
            ->active()
            ->where('is_main', true)
            ->first(['id']);

        if (!$branch) {
            $branch = Branch::query()->active()->ordered()->first(['id']);
        }

        return $branch ? (int) $branch->id : null;
    }

    // public function maids()
    // {
    //     return $this->hasMany(Maid::class);
    // }
}
