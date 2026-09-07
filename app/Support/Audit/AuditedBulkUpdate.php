<?php

namespace App\Support\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AuditedBulkUpdate
{
    /** Preserve the original bulk SQL semantics without triggering business model observers. */
    public static function update(Builder $query, string $modelClass, array $values): void
    {
        DB::connection($query->getConnection()->getName())->transaction(function () use ($query, $modelClass, $values) {
            (clone $query)->orderBy('id')->chunkById(200, function ($rows) use ($query, $modelClass, $values) {
                (clone $query)->whereIn('id', $rows->pluck('id'))->update($values);
                foreach ($rows as $row) {
                    $old = $new = [];
                    foreach ($values as $field => $value) {
                        if ($field !== 'updated_at' && ($row->{$field} ?? null) != $value) {
                            $old[$field] = $row->{$field} ?? null;
                            $new[$field] = $value;
                        }
                    }
                    if (!$new) {
                        continue;
                    }
                    /** @var Model $subject */
                    $subject = new $modelClass();
                    $subject->setRawAttributes(array_merge((array) $row, $values));
                    $subject->exists = true;
                    activity()->performedOn($subject)->event('updated')
                        ->withProperties(['old' => $old, 'attributes' => $new])
                        ->log('إعادة إسناد أعمال المستخدم');
                }
            });
        });
    }
}
