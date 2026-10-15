<?php

namespace App\Services\Hourly;

use App\Models\City;
use App\Support\PlusCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * يحوّل موقعًا ملصوقًا كما يعرضه Google Maps إلى إحداثيات، بدل أن يكتب المستخدم خط الطول والعرض:
 * - Plus Code قصير مع العنوان: «CWQC+4C9, Al Nahdah, Hafar Al Batin…» (يُستكمل بمركز المدينة المختارة أو بالعنوان)
 * - Plus Code كامل: «7GVPCWQC+4C9»
 * - رابط Google Maps (الكامل أو المختصر maps.app.goo.gl)
 * - إحداثيات: «28.4378, 45.9211»
 */
class LocationTextResolver
{
    private const SHORT_LINK_HOSTS = ['maps.app.goo.gl', 'goo.gl', 'g.co'];

    public function __construct(private CityLookupService $lookup) {}

    /** @return array{latitude: float, longitude: float, source: string, distance_km: ?float} */
    public function resolve(string $text, ?City $city = null): array
    {
        $text = trim($this->latinDigits($text));
        if (preg_match('~https?://\S+~i', $text, $url)) $text = $this->fromUrl($url[0]) ?? $text;

        if ($point = $this->coordinates($text)) return $this->result($point, 'coordinates', $city);

        if ($plus = PlusCode::extract($text)) {
            [$code, $locality] = $plus;
            if (PlusCode::isFull($code)) return $this->result(PlusCode::decode($code), 'plus_code', $city);
            $reference = $this->reference($city, $locality);
            if (!$reference) $this->fail('الرمز «'.$code.'» قصير ويحتاج مدينة: اختر المدينة أولًا أو الصق العنوان كاملًا كما في Google Maps.');

            return $this->result(PlusCode::recover($code, $reference[0], $reference[1]), 'plus_code', $city);
        }

        $this->fail('لم نتعرف على الموقع. الصق رمز الموقع من Google Maps (مثل CWQC+4C9, Al Nahdah, Hafar Al Batin) أو رابط الخريطة أو الإحداثيات.');
    }

    /**
     * نقطة مرجعية لاستكمال الرمز القصير (يكفي أن تكون ضمن ~50 كم): إحداثيات المدينة المختارة،
     * وإلا البحث عن اسمها، وإلا البحث عن المدينة الواردة في العنوان الملصوق.
     */
    private function reference(?City $city, string $locality): ?array
    {
        if ($city && is_numeric($city->latitude) && is_numeric($city->longitude)) return [(float) $city->latitude, (float) $city->longitude];
        foreach (array_filter([$city ? ($city->name_en ?: $city->name_ar) : null, $this->localityCity($locality)]) as $name) {
            try {
                $first = $this->lookup->locate($name)['results'][0] ?? null;
            } catch (ValidationException) {
                $first = null;
            }
            if ($first) return [(float) $first['latitude'], (float) $first['longitude']];
        }

        return null;
    }

    /** «Al Nahdah, Hafar Al Batin 39958, Saudi Arabia» ← «Hafar Al Batin»: آخر جزء بعد حذف الدولة والرمز البريدي. */
    private function localityCity(string $locality): ?string
    {
        $parts = collect(preg_split('/[,،]/u', $locality))
            ->map(fn ($part) => trim(preg_replace('/\b\d{4,6}\b/', '', $part)))
            ->reject(fn ($part) => $part === '' || preg_match('/^(saudi arabia|ksa|المملكة العربية السعودية|السعودية)$/iu', $part));

        return $parts->last();
    }

    private function coordinates(string $text): ?array
    {
        if (!preg_match('/^\s*\(?\s*(-?\d{1,2}(?:\.\d+)?)\s*[,،\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*\)?\s*$/u', $text, $m)) return null;
        [$lat, $lng] = [(float) $m[1], (float) $m[2]];

        return abs($lat) <= 90 && abs($lng) <= 180 ? [$lat, $lng] : null;
    }

    /** يستخرج الإحداثيات أو الرمز من رابط Google Maps؛ الرابط المختصر يُتبع تحويله (لنطاقات Google فقط). */
    private function fromUrl(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (in_array($host, self::SHORT_LINK_HOSTS, true)) $url = $this->expand($url) ?? $url;
        // الدبوس الفعلي (!3d!4d) أدق من مركز الشاشة (@lat,lng)، فيُفحص أولًا.
        foreach (['~!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)~', '~@(-?\d+\.\d+),(-?\d+\.\d+)~'] as $pattern) {
            if (preg_match($pattern, $url, $m)) return $m[1].','.$m[2];
        }
        // parse_str/urldecode: «+» مسافة و«%2B» هي علامة + في Plus Code.
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        foreach (['q', 'query', 'll', 'destination', 'center'] as $key) {
            if (is_string($query[$key] ?? null) && trim($query[$key]) !== '') return trim($query[$key]);
        }
        if (preg_match('~/maps/(?:place|search)/([^/?]+)~', $url, $m)) return urldecode($m[1]);

        return null;
    }

    private function expand(string $url): ?string
    {
        for ($hop = 0; $hop < 4; $hop++) {
            try {
                $response = Http::withoutRedirecting()->timeout(8)->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($url);
            } catch (\Throwable) {
                return null;
            }
            $next = $response->header('Location');
            if (!$response->redirect() || !$next) return $url;
            $host = strtolower((string) parse_url($next, PHP_URL_HOST));
            if (!in_array($host, self::SHORT_LINK_HOSTS, true) && !preg_match('/(^|\.)google\.[a-z.]+$/', $host)) return null;
            $url = $next;
        }

        return $url;
    }

    private function result(array $point, string $source, ?City $city): array
    {
        [$lat, $lng] = $point;
        $distance = $city && is_numeric($city->latitude) && is_numeric($city->longitude)
            ? round($this->distanceKm($lat, $lng, (float) $city->latitude, (float) $city->longitude), 1) : null;

        return ['latitude' => round($lat, 7), 'longitude' => round($lng, 7), 'source' => $source, 'distance_km' => $distance];
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function latinDigits(string $text): string
    {
        return strtr($text, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.']);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['location_text' => $message]);
    }
}
