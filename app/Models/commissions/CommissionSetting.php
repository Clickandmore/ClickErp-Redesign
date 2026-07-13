<?php

namespace App\Models\commissions;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class CommissionSetting extends Model
{
    protected $table = 'commission_settings';

    protected $fillable = ['service_type', 'method', 'value', 'created_by'];

    protected $casts = ['value' => 'decimal:2'];

    public static function serviceTypeOptions(): array
    {
        return [
            'electronic_ingaz_delegation' => 'تفويض إنجاز إلكتروني',
            'recruitment' => 'استقدام',
            'service_transfer' => 'نقل خدمة',
            'rental' => 'تأجير للنقل',
        ];
    }

    public function getServiceTypeLabelAttribute(): string
    {
        return self::serviceTypeOptions()[$this->service_type] ?? $this->service_type;
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
