<?php

namespace App\Support\Audit;

use Illuminate\Support\Facades\DB;

class AuditedSettings
{
    public static function updateOrInsert(string $table, array $keys, array $values): void
    {
        DB::transaction(function () use ($table, $keys, $values) {
            $before = (array) DB::table($table)->where($keys)->first();
            DB::table($table)->updateOrInsert($keys, $values);
            $after = array_merge($keys, $values);
            $new = $old = [];
            foreach ($after as $key => $value) {
                if (in_array($key, ['created_at', 'updated_at', 'updated_by'], true)) {
                    continue;
                }
                if (!array_key_exists($key, $before) || (string) $before[$key] !== (string) $value) {
                    $new[$key] = $value;
                    $old[$key] = $before[$key] ?? null;
                }
            }
            if ($new === []) {
                return;
            }
            activity('settings')->event($before ? 'updated' : 'created')
                ->withProperties(AuditProperties::sanitize([
                    'table' => $table,
                    'attributes' => array_merge($keys, $new),
                    'old' => array_merge($keys, $old),
                ]))->log('تغيير إعدادات النظام');
        });
    }
}
