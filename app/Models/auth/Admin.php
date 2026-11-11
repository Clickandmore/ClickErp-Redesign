<?php

namespace App\Models\auth;

use App\Models\ingaz\ComplaintComment;
use App\Models\Branch;
use App\Models\office\AdminOffice;
use App\Models\office\Office;
use App\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\commissions\CommissionMarketerOverride;
use App\Models\IngazDelegation;
use App\Models\ingaz\Maid;
use Nami\Finance\Models\Account;

class Admin extends Authenticatable
{
    use HasFactory, LogsActivity, Notifiable, SoftDeletes;
    use HasRoles { syncPermissions as protected syncPermissionsWithoutAudit; }

    public function syncPermissions(...$permissions)
    {
        return \App\Support\Audit\PermissionSync::run($this, fn () => $this->syncPermissionsWithoutAudit(...$permissions));
    }

    protected static function booted(): void
    {
        // تغيير كلمة المرور (من الملف الشخصي أو الاستعادة أو تعديل الموظف) يقطع اتصالات
        // المساعد الذكي، كما يُتوقع ممن يغيّرها لأنه يشك في تسرّب حسابه.
        static::updated(function (self $admin) {
            if ($admin->wasChanged('password') && \Illuminate\Support\Facades\Schema::hasTable('mcp_access_tokens')) {
                \App\Models\McpAccessToken::revokeAllFor((int) $admin->id);
            }
        });
    }

    protected $table ='admins';
    protected $guarded = [];
    protected $guard_name = 'admin';
    protected $casts = [
        'branch_id' => 'integer',
        'is_branch_manager' => 'boolean',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /**
     * تسجيل حركات إضافة/تعديل/حذف/استرجاع المستخدمين في سجل حركة النظام.
     *
     * الحقول الحساسة (كلمة المرور، التوكنات، بصمة الجلسة) مستثناة عمداً،
     * وكذلك last_activity_at لأن ميدلوير Admin يحدّثه كل دقيقة فيغرق السجل.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'username',
                'phone',
                'whatsapp',
                'email',
                'image',
                'admin_type',
                'status',
                'note',
                'sort',
                'role_id',
                'branch_id',
                'is_branch_manager',
                'lang',
                'is_developer',
                'sex_type',
                'identity_number',
                'identity_expiry_date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * تمييز الحذف النهائي عن الحذف المؤقت، لأن Eloquent يطلق حدث deleted في الحالتين.
     */
    public function tapActivity(ActivityContract $activity, string $eventName): void
    {
        if ($eventName === 'deleted' && $this->isForceDeleting()) {
            $activity->event = 'force_deleted';
            $activity->description = 'force_deleted';
        }
    }

    public function scopeNotSuspended(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull($this->getTable() . '.status')
                ->orWhere($this->getTable() . '.status', '!=', 'suspended');
        });
    }

    public function scopeActiveForOperations(Builder $query): Builder
    {
        return $query->notSuspended();
    }

    public function scopeVisibleToActiveBranch(Builder $query, ?self $admin = null): Builder
    {
        $access = app(\App\Services\BranchAccessService::class);
        $admin ??= auth('admin')->user();
        $branchId = $access->adminBranchId($admin);

        if (! $access->enabled() || ! $branchId || $access->canViewAllBranches($admin)) {
            return $query;
        }

        return $query->where($this->getTable() . '.branch_id', $branchId);
    }

    /**
     * Exclude only accounts managed exclusively as external-office agents.
     *
     * An internal user may also be linked to an external office. That link
     * must not make the user disappear from the main users page while they
     * still have an internal role or direct permissions.
     */
    public function scopeNotExternalOfficeAgents(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereDoesntHave('agentOfficeLinks')
                ->orWhereNotNull($this->getTable() . '.role_id')
                ->orWhereHas('roles')
                ->orWhereHas('permissions');
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function agentOfficeLink()
    {
        return $this->hasOne(AdminOffice::class, 'admin_id');
    }

    public function agentOfficeLinks()
    {
        return $this->hasMany(AdminOffice::class, 'admin_id');
    }

    public function agentOffices()
    {
        return $this->belongsToMany(Office::class, 'admin_offices', 'admin_id', 'office_id');
    }

    public function isExternalOfficeAgent(): bool
    {
        return $this->agentOfficeLinks()->exists();
    }

    public function isExternalOfficeOnlyAgent(): bool
    {
        return $this->isExternalOfficeAgent()
            && empty($this->role_id)
            && ! $this->roles()->exists()
            && ! $this->permissions()->exists();
    }

    public function maids()
    {
        return $this->hasMany(Maid::class);
    }

    public function ingazDelegations()
    {
        return $this->hasMany(IngazDelegation::class, 'marketer_id');
    }

    public function commissionOverrides()
    {
        return $this->hasMany(CommissionMarketerOverride::class, 'admin_id');
    }

    public function commissionPayableAccount()
    {
        return $this->belongsTo(Account::class, 'commission_payable_account_id');
    }

    public function complaintComments()
    {
        return $this->hasMany(ComplaintComment::class);
    }

    public function getContactPhoneAttribute(): ?string
    {
        return $this->normalizePhoneValue($this->attributes['phone'] ?? null);
    }

    public function getWhatsappPhoneAttribute(): ?string
    {
        return $this->normalizePhoneValue($this->attributes['whatsapp'] ?? null) ?: $this->contact_phone;
    }

    public function getVerificationPhoneAttribute($value): ?string
    {
        return $this->normalizePhoneValue($value) ?: $this->contact_phone;
    }

    public function getFormattedContactPhoneAttribute(): ?string
    {
        return $this->contact_phone ? formatSaudiPhone($this->contact_phone) : null;
    }

    public function getFormattedWhatsappPhoneAttribute(): ?string
    {
        return $this->whatsapp_phone ? formatSaudiPhone($this->whatsapp_phone) : null;
    }

    public function getFormattedVerificationPhoneAttribute(): ?string
    {
        return $this->verification_phone ? formatSaudiPhone($this->verification_phone) : null;
    }

    private function normalizePhoneValue($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

}
