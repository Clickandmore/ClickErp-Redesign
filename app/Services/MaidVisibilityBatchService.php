<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaidVisibilityBatchService
{
    public static function fingerprint(array $attributes): string
    {
        ksort($attributes);
        return hash('sha256', json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function apply(Builder $query, array $filters, int $adminId, string $previewToken): array
    {
        return DB::transaction(function () use ($query, $filters, $adminId, $previewToken) {
            $maids = (clone $query)->reorder('maids.id')->lockForUpdate()->get();
            abort_unless(hash_equals($previewToken, $this->previewToken($maids, $filters)), 409, 'تغيرت السير منذ المعاينة. يرجى تحديث العدد ثم إعادة التنفيذ.');
            $id = (string) Str::uuid();
            $visible = (bool) $filters['is_show'];
            DB::table('maid_visibility_batches')->insert([
                'id' => $id, 'admin_id' => $adminId, 'filters' => json_encode($filters),
                'is_show' => $visible, 'affected_count' => $maids->count(), 'created_at' => now(),
            ]);
            foreach ($maids->chunk(500) as $chunk) {
                $ids = $chunk->pluck('id')->all();
                $previous = $chunk->keyBy('id');
                DB::table('maids')->whereIn('id', $ids)->update([
                    'is_show' => $visible, 'visibility_batch_id' => $id, 'updated_at' => now(),
                ]);
                $items = DB::table('maids')->whereIn('id', $ids)->get()->map(fn ($row) => [
                    'batch_id' => $id, 'maid_id' => $row->id,
                    'previous_visibility' => (bool) $previous[$row->id]->is_show,
                    'fingerprint' => self::fingerprint((array) $row),
                ])->all();
                DB::table('maid_visibility_batch_items')->insert($items);
            }
            return ['batch_id' => $id, 'updated_count' => $maids->count()];
        });
    }

    public function previewToken($maids, array $filters): string
    {
        $state = $maids->map(fn ($maid) => [$maid->id, self::fingerprint($maid->getRawOriginal())])->values()->all();
        return hash_hmac('sha256', json_encode([$filters, $state]), config('app.key'));
    }

    public function undo(Builder $eligibleQuery, string $id, int $adminId): array
    {
        return DB::transaction(function () use ($eligibleQuery, $id, $adminId) {
            $batch = DB::table('maid_visibility_batches')->where('id', $id)->where('admin_id', $adminId)->lockForUpdate()->first();
            abort_unless($batch, 404);
            abort_if($batch->is_show || $batch->undone_at, 422, 'هذه الدفعة غير قابلة للتراجع.');
            $items = DB::table('maid_visibility_batch_items')->where('batch_id', $id)->get()->keyBy('maid_id');
            $maids = (clone $eligibleQuery)->where('visibility_batch_id', $id)->where('is_show', false)->reorder('maids.id')->lockForUpdate()->get();
            $restoredIds = [];
            foreach ($maids as $maid) {
                $item = $items->get($maid->id);
                if (!$item || !$item->previous_visibility || !hash_equals($item->fingerprint, self::fingerprint($maid->getRawOriginal()))) {
                    continue;
                }
                $restoredIds[] = $maid->id;
            }
            foreach (array_chunk($restoredIds, 500) as $ids) {
                DB::table('maids')->whereIn('id', $ids)->update(['is_show' => true, 'visibility_batch_id' => null, 'updated_at' => now()]);
            }
            $restored = count($restoredIds);
            DB::table('maid_visibility_batches')->where('id', $id)->update(['undone_at' => now(), 'restored_count' => $restored]);
            return ['restored_count' => $restored, 'skipped_count' => $batch->affected_count - $restored];
        });
    }
}
