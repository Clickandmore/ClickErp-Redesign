<?php

namespace App\Support\Audit;

use Illuminate\Database\Eloquent\Model;

class PermissionSync
{
    public static function run(Model $model, callable $sync)
    {
        return $model->getConnection()->transaction(function () use ($model, $sync) {
            $before = $model->exists ? $model->permissions()->pluck('permissions.id')->all() : [];
            $result = $sync();
            $after = $model->permissions()->pluck('permissions.id')->all();
            $removed = array_values(array_diff($before, $after));
            // This installed Spatie version emits attach events during sync, but no detach event.
            if ($removed !== []) {
                activity('permissions')->performedOn($model)->event('permission_detached')
                    ->withProperties(['assignment_ids' => $removed])->log('إلغاء صلاحيات');
            }
            return $result;
        });
    }
}
