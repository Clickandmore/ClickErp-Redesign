<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $fillable = [
        'subject',
        'message',
        'category',
        'priority',
        'status',
        'created_by',
        'assigned_to',
    ];

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }
}
