<?php

namespace App\Models;

use App\Models\auth\Admin;
use App\Models\ingaz\Notification;
use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'channel',
        'recipient_type',
        'user_id',
        'admin_id',
        'notification_id',
        'recipient_name',
        'recipient_phone',
        'title',
        'message',
        'provider',
        'status',
        'response',
        'meta',
        'sent_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
    ];

    public function scopeWhatsAppAccount($query, string $account)
    {
        $query->where('channel', 'whatsapp');

        return $account === 'unrecorded'
            ? $query->whereNull('meta->whatsapp_account')
            : $query->where('meta->whatsapp_account', $account);
    }

    public function getWhatsappAccountLabelAttribute(): ?string
    {
        if ($this->channel !== 'whatsapp') {
            return null;
        }

        return match ($this->meta['whatsapp_account'] ?? null) {
            'default' => __('whatsapp.general'),
            'logistics' => __('whatsapp.logistics'),
            default => __('whatsapp.unrecorded'),
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    public function getDisplayTitleAttribute(): ?string
    {
        return $this->decodePotentialTranslationPayload($this->title);
    }

    public function getDisplayMessageAttribute(): string
    {
        return $this->decodePotentialTranslationPayload($this->message) ?? '';
    }

    protected function decodePotentialTranslationPayload($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded['ar']
                ?? $decoded['en']
                ?? collect($decoded)->filter()->first()
                ?? null;
        }

        return (string) $value;
    }
}
