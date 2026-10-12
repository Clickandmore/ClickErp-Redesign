<?php

namespace App\Services\SystemUpdates;

use App\Models\SystemUpdate;
use App\Models\SystemUpdateMedia;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * ما تعرضه هذه النسخة لنسخ العملاء عبر GET /api/system-updates/feed.
 *
 * التزامن تدريجي: العميل يرسل since (server_time من آخر رد) فيصله كل ما تغيّر
 * منذها — بما فيه المحذوف وغير المنشور كـ active=false ليحذفه من عنده.
 */
class SystemUpdateFeed
{
    public const VERSION = 1;

    public const MAX_ITEMS = 500;

    public function payload(?CarbonInterface $since): array
    {
        // نلتقط الوقت قبل الاستعلام: أي تعديل أثناءه يظهر في السحب التالي بدل أن يضيع
        $serverTime = Carbon::now();

        $updates = SystemUpdate::withTrashed()
            ->where('source', SystemUpdate::SOURCE_LOCAL)
            ->when($since, fn ($query) => $query->where('updated_at', '>=', $since))
            ->with('media')
            ->orderBy('updated_at')
            ->limit(self::MAX_ITEMS)
            ->get();

        // في السحب الأول لا معنى لإرسال ما لم يكن منشورًا أصلًا
        if (! $since) {
            $updates = $updates->filter(fn (SystemUpdate $update) => $update->isActiveInFeed());
        }

        return [
            'feed_version' => self::VERSION,
            'server_time' => $serverTime->toIso8601String(),
            'source' => [
                'name' => config('app.name'),
                'url' => rtrim((string) config('app.url'), '/'),
            ],
            'updates' => $updates->values()->map(fn (SystemUpdate $update) => $this->item($update))->all(),
        ];
    }

    private function item(SystemUpdate $update): array
    {
        if (! $update->isActiveInFeed()) {
            return ['uuid' => $update->uuid, 'active' => false];
        }

        return [
            'uuid' => $update->uuid,
            'active' => true,
            'title' => $update->title,
            'summary' => $update->summary,
            'body' => $update->body,
            'category' => $update->category,
            'module_key' => $update->module_key,
            'link_url' => $update->link_url,
            'link_label' => $update->link_label,
            'version' => $update->version,
            'published_at' => optional($update->published_at)->toIso8601String(),
            'banner_until' => optional($update->banner_until)->toIso8601String(),
            'updated_at' => optional($update->updated_at)->toIso8601String(),
            'media' => $update->media->map(fn (SystemUpdateMedia $media) => [
                'type' => $media->type,
                'url' => $media->publicUrl(),
                'caption' => $media->caption,
                'sort_order' => (int) $media->sort_order,
            ])->filter(fn (array $media) => filled($media['url']))->values()->all(),
        ];
    }
}
