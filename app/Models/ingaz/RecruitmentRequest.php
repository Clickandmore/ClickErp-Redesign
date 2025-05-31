<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentRequest extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'maid_id',
        'user_id',
        'order_code',
        'job_type_id',
        'payment_method',
        'status'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function maid(): BelongsTo
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function jobType(): BelongsTo
    {
        return $this->belongsTo(JobType::class, 'job_type_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->order_code = static::generateUniqueCode();
            $model->payment_method = 'cash';
            $model->status = 'new';
        });
    }

    protected static function generateUniqueCode()
    {
        do {
            $code = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('order_code', $code)->exists());

        return $code;
    }
}
