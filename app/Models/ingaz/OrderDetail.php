<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OrderDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = [];
    protected $with = ['package', 'address'];

    protected static function booted()
    {
        static::saved(function (OrderDetail $orderDetail) {
            if (empty($orderDetail->admin_id)) {
                return;
            }

            $order = Order::query()->find($orderDetail->order_id);

            if ($order && method_exists($order, 'syncBranchFromResponsibleAdmin')) {
                $order->syncBranchFromResponsibleAdmin((int) $orderDetail->admin_id, true);
            }
        });
    }

    // relations
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function address()
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }


}
