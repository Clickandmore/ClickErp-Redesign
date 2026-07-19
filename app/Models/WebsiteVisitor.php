<?php

namespace App\Models;

use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Model;

class WebsiteVisitor extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'ip_address',
        'user_agent',
        'current_url',
        'landing_url',
        'referrer',
        'page_title',
        'locale',
        'timezone',
        'screen_width',
        'screen_height',
        'session_started_at',
        'last_seen_at',
        'conversation_started_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'session_started_at' => 'datetime',
        'conversation_started_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(WebsiteVisitorMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(WebsiteVisitorMessage::class)->latestOfMany();
    }

    public function pageViews()
    {
        return $this->hasMany(WebsiteVisitorPageView::class);
    }
}
