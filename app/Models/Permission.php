<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Permission extends SpatiePermission
{

    use LogsActivity;

    protected static ?bool $hasIsActiveColumn = null;

    public $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        if (static::hasIsActiveColumn()) {
            static::addGlobalScope('active', function (Builder $builder) {
                $builder->where($builder->qualifyColumn('is_active'), true);
            });
        }
    }

    protected static function hasIsActiveColumn(): bool
    {
        if (static::$hasIsActiveColumn !== null) {
            return static::$hasIsActiveColumn;
        }

        return static::$hasIsActiveColumn = Schema::hasColumn((new static())->getTable(), 'is_active');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
