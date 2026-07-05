<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalContractExpiryReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_contract_id',
        'token',
        'channel',
        'status',
        'response',
        'expires_on',
        'sent_at',
        'responded_at',
        'sms_sent',
        'whatsapp_sent',
        'send_error',
        'employee_sms_sent_at',
        'employee_sms_recipients',
        'employee_sms_error',
        'payment_id',
        'payment_url',
        'payment_status',
        'extension_id',
        'contract_effect_type',
        'contract_effect_applied_at',
        'contract_effect_error',
    ];

    protected $casts = [
        'expires_on' => 'date',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'employee_sms_sent_at' => 'datetime',
        'contract_effect_applied_at' => 'datetime',
        'sms_sent' => 'boolean',
        'whatsapp_sent' => 'boolean',
        'employee_sms_recipients' => 'array',
    ];

    public function rentalContract()
    {
        return $this->belongsTo(RentalContract::class, 'rental_contract_id');
    }
}
