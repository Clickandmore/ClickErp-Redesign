<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderNote extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    protected $fillable = ['order_id', 'admin_id', 'note'];

    protected $casts = [
        'order_id' => 'integer',
        'admin_id' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromOrder($this->order_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
