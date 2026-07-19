<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteVisitorPageView extends Model
{
    protected $fillable = [
        'website_visitor_id',
        'url',
        'title',
        'referrer',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function visitor()
    {
        return $this->belongsTo(WebsiteVisitor::class, 'website_visitor_id');
    }
}
