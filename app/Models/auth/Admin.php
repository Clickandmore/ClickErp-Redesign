<?php

namespace App\Models\auth;

use App\Models\ingaz\ComplaintComment;
use App\Models\Branch;
use App\Models\office\AdminOffice;
use App\Models\office\Office;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\ingaz\Maid;

class Admin extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;
    protected $table ='admins';
    protected $guarded = [];
    protected $guard_name = 'admin';
    protected $casts = [
        'branch_id' => 'integer',
        'is_branch_manager' => 'boolean',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];


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
