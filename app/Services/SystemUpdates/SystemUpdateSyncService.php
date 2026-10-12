<?php

namespace App\Services\SystemUpdates;

use App\Models\SystemUpdate;
use App\Models\SystemUpdateMedia;
use App\Support\SystemUpdatesSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * يسحب التحديثات من مصدر التحديثات المضبوط في السوبر أدمن ويخزّنها محليًا كـ remote.
 *
 * العرض للموظفين وتأكيد الاطلاع يعملان على النسخة المحلية فقط، فتوقف المصدر لا
 * يؤثر على لوحة العميل.
 */
class SystemUpdateSyncService
{
    public const FEED_PATH = '/api/system-updates/feed';

    private const ALLOWED_CATEGORIES = ['feature', 'improvement', 'fix', 'important'];

    private const ALLOWED_MEDIA = [SystemUpdateMedia::TYPE_IMAGE, SystemUpdateMedia::TYPE_VIDEO, SystemUpdateMedia::TYPE_EMBED, SystemUpdateMedia::TYPE_PDF];

    private const AUTO_SYNC_LOCK = 'system-updates:auto-sync';

    private int $timeout = 20;

    /**
     * @return array{received: int, saved: int, removed: int}
     */
    public function sync(bool $full = false): array
    {
        $settings = SystemUpdatesSettings::all();
        $key = SystemUpdatesSettings::sourceKey();

        if ($settings['source_url'] === '' || $key === null) {
            throw new RuntimeException('حدد رابط مصدر التحديثات ومفتاح الربط أولًا.');
        }

        $state = SystemUpdatesSettings::state();
        $since = $full ? null : $state['cursor'];
        SystemUpdatesSettings::recordState(['last_attempt_at' => now()->toDateTimeString()]);

        try {
            $payload = $this->fetch($settings['source_url'], $key, $since);
            $result = $this->apply($payload, (string) parse_url($settings['source_url'], PHP_URL_HOST));
        } catch (\Throwable $exception) {
            SystemUpdatesSettings::recordState(['last_error' => Str::limit($exception->getMessage(), 500)]);

            throw $exception;
        }

        SystemUpdatesSettings::recordState([
            'cursor' => $payload['server_time'],
            'last_synced_at' => now()->toDateTimeString(),
            'last_error' => null,
            'last_received' => $result['received'],
        ]);

        return $result;
    }

    /**
     * احتياط لو لم يكن cron مفعّلًا على سيرفر العميل: عند فتح موظف لأي صفحة تُسحب التحديثات
     * بعد إرسال الصفحة له (فلا تتأخر)، ومرة واحدة على الأكثر كل مدة مزامنة.
     */
    public static function syncAfterResponseIfDue(): void
    {
        if (! SystemUpdatesSettings::syncConfigured()) {
            return;
        }

        $minutes = SystemUpdatesSettings::syncIntervalMinutes();
        $lastAttempt = SystemUpdatesSettings::state()['last_attempt_at'];

        if ($lastAttempt && Carbon::parse($lastAttempt)->greaterThan(now()->subMinutes($minutes))) {
            return;
        }

        if (! Cache::add(self::AUTO_SYNC_LOCK, true, now()->addMinutes($minutes))) {
            return;
        }

        app()->terminating(function () {
            try {
                $service = app(self::class);
                $service->timeout = 8;
                $service->sync();
            } catch (\Throwable $exception) {
                // الخطأ محفوظ في حالة المزامنة ويظهر في Super Admin
            }
        });
    }

    /** يتأكد من الرابط والمفتاح دون تعديل أي بيانات. */
    public function testConnection(string $baseUrl, string $key): array
    {
        $payload = $this->fetch(SystemUpdatesSettings::normalizeBaseUrl($baseUrl), $key, now()->toIso8601String());

        return [
            'source_name' => (string) Arr::get($payload, 'source.name', ''),
            'server_time' => (string) $payload['server_time'],
        ];
    }

    private function fetch(string $baseUrl, string $key, ?string $since): array
    {
        if (! Str::startsWith($baseUrl, ['https://', 'http://'])) {
            throw new RuntimeException('رابط المصدر يجب أن يبدأ بـ https://');
        }

        $response = Http::acceptJson()
            ->timeout($this->timeout)
            ->withHeaders(['X-System-Updates-Key' => $key])
            ->get($baseUrl . self::FEED_PATH, array_filter(['since' => $since]));

        $this->guardResponse($response);

        $payload = $response->json();
        if (! is_array($payload) || ! isset($payload['server_time']) || ! is_array($payload['updates'] ?? null)) {
            throw new RuntimeException('الرد من المصدر ليس بصيغة تحديثات النظام. تأكد أن الرابط يشير إلى نسخة ClickERP.');
        }

        return $payload;
    }

    private function guardResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(match ($response->status()) {
            401, 403 => 'المصدر رفض مفتاح الربط. تأكد من المفتاح وأنه مفعّل في نسخة المصدر.',
            404 => 'لم يُعثر على واجهة التحديثات في هذا الرابط. تأكد من الدومين وأن نسخة المصدر محدّثة.',
            429 => 'المصدر يحد عدد الطلبات حاليًا، أعد المحاولة بعد دقيقة.',
            default => 'تعذر الاتصال بالمصدر (HTTP ' . $response->status() . ').',
        });
    }

    private function apply(array $payload, string $sourceHost): array
    {
        $result = ['received' => 0, 'saved' => 0, 'removed' => 0];

        foreach ($payload['updates'] as $item) {
            $uuid = is_array($item) ? (string) ($item['uuid'] ?? '') : '';
            if (! Str::isUuid($uuid)) {
                continue;
            }

            $result['received']++;

            $existing = SystemUpdate::withTrashed()->where('uuid', $uuid)->first();

            // تحديث كُتب في هذه النسخة لا يُستبدل أبدًا (مثلًا لو وُجّهت النسخة لنفسها)
            if ($existing && ! $existing->isRemote()) {
                continue;
            }

            if (empty($item['active'])) {
                if ($existing) {
                    $existing->forceDelete();
                    $result['removed']++;
                }

                continue;
            }

            DB::transaction(function () use ($existing, $item, $uuid, $sourceHost) {
                $update = $existing ?: new SystemUpdate(['uuid' => $uuid]);
                $update->fill([
                    'source' => SystemUpdate::SOURCE_REMOTE,
                    'source_host' => $sourceHost ?: null,
                    'title' => Str::limit(trim((string) ($item['title'] ?? '')), 250, '') ?: 'تحديث',
                    'summary' => Str::limit(trim((string) ($item['summary'] ?? '')), 500, '') ?: null,
                    'body' => is_string($item['body'] ?? null) ? $item['body'] : null,
                    'category' => in_array($item['category'] ?? null, self::ALLOWED_CATEGORIES, true) ? $item['category'] : 'feature',
                    'module_key' => is_string($item['module_key'] ?? null) ? Str::limit($item['module_key'], 80, '') : null,
                    'link_url' => $this->safeLink($item['link_url'] ?? null),
                    'link_label' => Str::limit(trim((string) ($item['link_label'] ?? '')), 80, '') ?: null,
                    'version' => Str::limit(trim((string) ($item['version'] ?? '')), 40, '') ?: null,
                    'status' => SystemUpdate::STATUS_PUBLISHED,
                    'is_shared' => false,
                    'published_at' => $this->date($item['published_at'] ?? null) ?? now(),
                    'banner_until' => $this->date($item['banner_until'] ?? null),
                    'deleted_at' => null,
                ]);
                $update->save();

                $update->media()->delete();
                foreach (array_values((array) ($item['media'] ?? [])) as $index => $media) {
                    $url = is_array($media) ? (string) ($media['url'] ?? '') : '';
                    $type = is_array($media) ? ($media['type'] ?? null) : null;

                    if (! in_array($type, self::ALLOWED_MEDIA, true) || ! Str::startsWith($url, ['https://', 'http://'])) {
                        continue;
                    }

                    $update->media()->create([
                        'type' => $type,
                        'url' => Str::limit($url, 2000, ''),
                        'caption' => Str::limit(trim((string) ($media['caption'] ?? '')), 250, '') ?: null,
                        'sort_order' => (int) ($media['sort_order'] ?? $index),
                    ]);
                }
            });

            $result['saved']++;
        }

        return $result;
    }

    /** رابط داخلي (/dashboard/...) يعمل كما هو لأن كل النسخ على نفس الكود. */
    private function safeLink(mixed $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $internal = Str::startsWith($url, '/') && ! Str::startsWith($url, '//');
        $external = Str::startsWith($url, ['https://', 'http://']);

        return ($internal || $external) ? Str::limit($url, 1000, '') : null;
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
