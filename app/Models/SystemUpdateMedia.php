<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SystemUpdateMedia extends Model
{
    public const DISK = 'public';

    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_EMBED = 'embed';
    public const TYPE_PDF = 'pdf';

    protected $table = 'system_update_media';

    protected $guarded = [];

    public function systemUpdate()
    {
        return $this->belongsTo(SystemUpdate::class);
    }

    /**
     * الملف المرفوع هنا يُخدم من قرص public؛ المسحوب من مصدر خارجي يبقى رابطًا مطلقًا
     * على سيرفر المصدر فلا يتكرر رفع الفيديو على كل نسخة.
     */
    public function publicUrl(): ?string
    {
        if (filled($this->path)) {
            $url = Storage::disk(self::DISK)->url($this->path);

            return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
        }

        return $this->url ?: null;
    }

    /** رابط iframe آمن لـ YouTube / Vimeo / Loom فقط، وإلا null فيُعرض كرابط عادي. */
    public function embedUrl(): ?string
    {
        return self::toEmbedUrl((string) $this->url);
    }

    public static function toEmbedUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return null;
        }

        $host = Str::lower(preg_replace('/^www\./i', '', $parts['host']));
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $id = fn (string $value) => preg_match('/^[A-Za-z0-9_-]{6,64}$/', $value) ? $value : null;

        if (in_array($host, ['youtube.com', 'm.youtube.com'], true)) {
            $videoId = $path === '/watch'
                ? $id((string) ($query['v'] ?? ''))
                : (preg_match('#^/(?:embed|shorts|live)/([^/]+)#', $path, $m) ? $id($m[1]) : null);

            return $videoId ? 'https://www.youtube-nocookie.com/embed/' . $videoId : null;
        }

        if ($host === 'youtu.be') {
            $videoId = $id(trim($path, '/'));

            return $videoId ? 'https://www.youtube-nocookie.com/embed/' . $videoId : null;
        }

        if ($host === 'vimeo.com' && preg_match('#^/(\d+)#', $path, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        if ($host === 'loom.com' && preg_match('#^/(?:share|embed)/([^/]+)#', $path, $m) && $id($m[1])) {
            return 'https://www.loom.com/embed/' . $m[1];
        }

        return null;
    }
}
