<?php

namespace App\Services\SystemUpdates;

use App\Models\SystemUpdate;
use App\Models\SystemUpdateMedia;
use App\Support\SystemUpdatesSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * حفظ تحديث من لوحة السوبر أدمن مع وسائطه.
 */
class SystemUpdateEditor
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov'];

    public const DOCUMENT_EXTENSIONS = ['pdf'];

    public function save(SystemUpdate $update, array $data, array $files = []): SystemUpdate
    {
        $uploadedPaths = [];

        try {
            return DB::transaction(function () use ($update, $data, $files, &$uploadedPaths) {
                $status = $data['status'] === SystemUpdate::STATUS_PUBLISHED
                    ? SystemUpdate::STATUS_PUBLISHED
                    : SystemUpdate::STATUS_DRAFT;

                $publishedAt = filled($data['published_at'] ?? null)
                    ? Carbon::parse($data['published_at'])
                    : ($status === SystemUpdate::STATUS_PUBLISHED ? ($update->published_at ?: now()) : null);

                $bannerUntil = filled($data['banner_until'] ?? null)
                    ? Carbon::parse($data['banner_until'])
                    : ($publishedAt ? $publishedAt->copy()->addDays(SystemUpdatesSettings::bannerDays()) : null);

                $update->fill([
                    'source' => SystemUpdate::SOURCE_LOCAL,
                    'title' => trim($data['title']),
                    'summary' => trim((string) ($data['summary'] ?? '')) ?: null,
                    'body' => trim((string) ($data['body'] ?? '')) ?: null,
                    'category' => $data['category'],
                    'module_key' => ($data['module_key'] ?? null) ?: null,
                    'link_url' => trim((string) ($data['link_url'] ?? '')) ?: null,
                    'link_label' => trim((string) ($data['link_label'] ?? '')) ?: null,
                    'version' => trim((string) ($data['version'] ?? '')) ?: null,
                    'status' => $status,
                    'is_shared' => (bool) ($data['is_shared'] ?? false),
                    'published_at' => $publishedAt,
                    'banner_until' => $bannerUntil,
                ]);
                $update->save();

                $this->removeMedia($update, (array) ($data['remove_media'] ?? []));
                $this->saveCaptions($update, (array) ($data['media_captions'] ?? []));

                $nextOrder = (int) $update->media()->max('sort_order') + 1;

                foreach ($files as $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }

                    $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
                    $path = $file->store('system-updates/' . $update->uuid, SystemUpdateMedia::DISK);
                    $uploadedPaths[] = $path;

                    $type = match (true) {
                        in_array($extension, self::VIDEO_EXTENSIONS, true) => SystemUpdateMedia::TYPE_VIDEO,
                        in_array($extension, self::DOCUMENT_EXTENSIONS, true) => SystemUpdateMedia::TYPE_PDF,
                        default => SystemUpdateMedia::TYPE_IMAGE,
                    };

                    $update->media()->create([
                        'type' => $type,
                        'path' => $path,
                        // اسم الملف الأصلي يظهر للموظف على بطاقة الـ PDF بدل اسم التخزين العشوائي
                        'caption' => $type === SystemUpdateMedia::TYPE_PDF ? mb_substr($file->getClientOriginalName(), 0, 250) : null,
                        'sort_order' => $nextOrder++,
                    ]);
                }

                foreach ($this->embedLines((string) ($data['embed_urls'] ?? '')) as $url) {
                    $update->media()->create([
                        'type' => SystemUpdateMedia::TYPE_EMBED,
                        'url' => $url,
                        'sort_order' => $nextOrder++,
                    ]);
                }

                // أي تعديل على الوسائط يجب أن يصل لنسخ العملاء في السحب التالي
                $update->touch();

                return $update->fresh('media');
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $path) {
                Storage::disk(SystemUpdateMedia::DISK)->delete($path);
            }

            throw $exception;
        }
    }

    public function delete(SystemUpdate $update): void
    {
        DB::transaction(function () use ($update) {
            if ($update->isRemote()) {
                $update->forceDelete();

                return;
            }

            $this->removeMedia($update, $update->media()->pluck('id')->all());
            $update->delete();
        });

        Storage::disk(SystemUpdateMedia::DISK)->deleteDirectory('system-updates/' . $update->uuid);
    }

    /** @return string[] */
    public function embedLines(string $raw): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $raw) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function removeMedia(SystemUpdate $update, array $mediaIds): void
    {
        $ids = array_filter(array_map('intval', $mediaIds));
        if ($ids === []) {
            return;
        }

        $update->media()->whereIn('id', $ids)->get()->each(function (SystemUpdateMedia $media) {
            if ($media->path) {
                Storage::disk(SystemUpdateMedia::DISK)->delete($media->path);
            }

            $media->delete();
        });
    }

    private function saveCaptions(SystemUpdate $update, array $captions): void
    {
        foreach ($captions as $mediaId => $caption) {
            $update->media()->whereKey((int) $mediaId)->update([
                'caption' => trim((string) $caption) ?: null,
            ]);
        }
    }
}
