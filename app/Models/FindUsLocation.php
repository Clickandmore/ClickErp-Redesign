<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FindUsLocation extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $fillable = [
        'branch_name',
        'branch_address',
        'sort_order',
        'map_embed_url',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * رابط تضمين Google Maps مبني من الإحداثيات (لا يحتاج مفتاح API).
     */
    public static function embedUrlFromCoordinates(float $latitude, float $longitude): string
    {
        $point = sprintf('%.7F,%.7F', $latitude, $longitude);

        return 'https://maps.google.com/maps?q=' . $point . '&z=16&hl=ar&output=embed';
    }

    /**
     * هل الرابط مولَّد تلقائيًا من الإحداثيات (وليس كود تضمين لصقه المستخدم)؟
     */
    public static function isCoordinatesEmbedUrl(?string $url): bool
    {
        $parts = parse_url((string) $url);
        parse_str((string) ($parts['query'] ?? ''), $query);

        return strtolower((string) ($parts['host'] ?? '')) === 'maps.google.com'
            && rtrim((string) ($parts['path'] ?? ''), '/') === '/maps'
            && ($query['output'] ?? null) === 'embed';
    }
}
