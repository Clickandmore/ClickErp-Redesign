<?php

namespace App\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RecordPermissionActivity
{
    public function handle($event): void
    {
        $values = $event->rolesOrIds ?? $event->permissionsOrIds ?? [];
        $values = $values instanceof Model ? [$values] : ($values instanceof Collection ? $values->all() : (array) $values);
        $ids = array_values(array_map(fn ($value) => $value instanceof Model ? $value->getKey() : $value, $values));
        if ($ids === []) {
            return;
        }
        $action = Str::snake(class_basename($event));
        activity('permissions')->performedOn($event->model)->event($action)
            ->withProperties(['assignment_ids' => $ids])
            ->log(match ($action) {
                'role_attached' => 'إسناد دور للمستخدم',
                'role_detached' => 'إلغاء إسناد دور للمستخدم',
                'permission_attached' => 'منح صلاحيات',
                default => 'إلغاء صلاحيات',
            });
    }
}
