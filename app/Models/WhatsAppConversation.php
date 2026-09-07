<?php

namespace App\Models;

use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Model;

class WhatsAppConversation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $table = 'whatsapp_conversations';

    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'customer_service_window_expires_at' => 'datetime',
        'unread_count' => 'integer',
    ];

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim((string) ($this->user?->name ?: $this->profile_name)) ?: $this->phone;
    }

    public function customerServiceWindowIsOpen(): bool
    {
        return $this->customer_service_window_expires_at !== null
            && $this->customer_service_window_expires_at->isFuture();
    }
}
