<?php

namespace App\Support\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ActivityRelations
{
    public static function load(Collection $activities): void
    {
        foreach (['causer', 'subject'] as $relation) {
            foreach ($activities->groupBy($relation . '_type') as $type => $group) {
                foreach ($group as $activity) {
                    $activity->setRelation($relation, null);
                }
                $class = Relation::getMorphedModel($type) ?: $type;
                if (!$class || !class_exists($class) || !is_subclass_of($class, Model::class)) {
                    continue;
                }
                $model = new $class();
                if (!Schema::connection($model->getConnectionName())->hasTable($model->getTable())) {
                    continue;
                }
                $query = $model->newQuery();
                if ($relation === 'causer' && in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                    $query->withTrashed();
                }
                $records = $query->whereKey($group->pluck($relation . '_id')->filter()->unique()->all())->get()->keyBy($model->getKeyName());
                foreach ($group as $activity) {
                    $activity->setRelation($relation, $records->get($activity->{$relation . '_id'}));
                }
            }
        }
    }
}
