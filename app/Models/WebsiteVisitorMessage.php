<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class WebsiteVisitorMessage extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $fillable = [
        'website_visitor_id',
        'sender',
        'admin_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function visitor()
    {
        return $this->belongsTo(WebsiteVisitor::class, 'website_visitor_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
