<?php

namespace App\Services\Hourly;

use Illuminate\Support\Facades\{Cache, Http};
use Illuminate\Validation\ValidationException;

class CityLookupService
{
    /**
     * ترميز جغرافي لعنوان عميل في معالج الحجز — بديل Geocoding API المدفوع.
     *
     * يقبل إحداثيات (ترميز عكسي عند الضغط على الخريطة) أو نصًا (بحث بالاسم)،
     * ويعيد شكلًا موحدًا يطابق ما تنتظره الواجهة من مزود Google:
     * الإحداثيات، والعنوان المقروء، وقائمة مكوناته لمطابقة المدينة والحي في القوائم.
     *
     * @return array{latitude: float, longitude: float, address: string, parts: array<int, string>}
     */
    public function address(?float $latitude = null, ?float $longitude = null, ?string $query = null): array
    {
        $reverse = $latitude !== null && $longitude !== null;
        $cacheKey = 'hourly-address:'.sha1($reverse ? round($latitude, 5).','.round($longitude, 5) : (string) $query);

        return Cache::remember($cacheKey, 86400, function () use ($reverse, $latitude, $longitude, $query) {
            // نفس القفل المشترك الذي تستخدمه بقية نداءات Nominatim احترامًا لسياسة الخدمة العامة.
            if (!Cache::add('hourly-nominatim-request', true, 2)) {
                throw ValidationException::withMessages(['address' => 'انتظر ثانيتين ثم أعد المحاولة.']);
            }

            $search = \App\Support\HourlyRentalSettings::get('geocoding_url', 'https://nominatim.openstreetmap.org/search');
            $url = $reverse ? preg_replace('~/search/?$~', '/reverse', $search) : $search;
            $fetch = function (array $parameters) use ($url, $reverse) {
                try {
                    $response = Http::withHeaders(['User-Agent' => 'HourlyRentalBooking/1.0 ('.config('app.url').')'])
                        ->timeout(12)->get($url, $parameters + ['format' => 'jsonv2', 'addressdetails' => 1, 'accept-language' => 'ar']);
                    if (!$response->successful()) return null;
                    $row = $response->json();
                    if ($reverse) { if (!is_array($row) || isset($row['error'])) return null; }
                    else { $row = is_array($row) ? ($row[0] ?? null) : null; if (!is_array($row)) return null; }
                    return is_numeric($row['lat'] ?? null) && is_numeric($row['lon'] ?? null) ? $row : null;
                } catch (\Throwable $e) {
                    return null;
                }
            };

            if ($reverse) {
                $row = $fetch(['lat' => $latitude, 'lon' => $longitude, 'zoom' => 18]);
            } else {
                $row = null;
                // Nominatim لا يتعرّف على بادئة «حي»، فنعيد المحاولة بدونها قبل الاستسلام.
                foreach (self::searchVariants((string) $query) as $index => $variant) {
                    if ($index > 0) usleep(1100000);
                    $row = $fetch(['q' => $variant.', Saudi Arabia', 'countrycodes' => 'sa', 'limit' => 1]);
                    if ($row) break;
                }
            }

            if (!$row) {
                throw ValidationException::withMessages([
                    'address' => $reverse
                        ? 'تعذر قراءة العنوان من الخريطة. أدخل تفاصيل العنوان يدويًا.'
                        : 'لم يتم العثور على العنوان. جرّب اسمًا أوضح أو حدد الموقع على الخريطة.',
                ]);
            }

            // مكونات العنوان تُستخدم لمطابقة المدينة والحي؛ الترتيب من الأخص إلى الأعم.
            $parts = array_values(array_filter(array_map(
                fn ($value) => is_string($value) ? trim($value) : null,
                (array) ($row['address'] ?? [])
            )));

            return [
                'latitude' => (float) $row['lat'],
                'longitude' => (float) $row['lon'],
                'address' => (string) ($row['display_name'] ?? ''),
                'parts' => $parts,
            ];
        });
    }

    /**
     * صيغ البحث بالترتيب: النص كما كتبه الموظف، ثم بلا بادئة «حي» التي لا يتعرف عليها Nominatim.
     *
     * @return array<int, string>
     */
    private static function searchVariants(string $query): array
    {
        $query = trim(preg_replace('/\s+/u', ' ', str_replace('،', ',', $query)));
        $stripped = trim(preg_replace('/(^|[,\s])حي\s+/u', '$1', $query), " \t,");

        return $stripped !== '' && $stripped !== $query ? [$query, $stripped] : [$query];
    }

    public function translate(string $text, string $source, string $target): string
    {
        return Cache::remember('hourly-city-translation:'.sha1($text.$source.$target),86400,function() use($text,$source,$target) {
            try {
                $response=Http::timeout(12)->get('https://api.mymemory.translated.net/get',['q'=>$text,'langpair'=>$source.'|'.$target]);
                $translated=$response->json('responseData.translatedText');
                if(!$response->successful() || (int)$response->json('responseStatus')!==200 || !is_string($translated) || trim($translated)==='' || mb_strlen($translated)>150) throw new \RuntimeException();
                return html_entity_decode($translated,ENT_QUOTES|ENT_HTML5,'UTF-8');
            } catch (\Throwable $e) { throw ValidationException::withMessages(['text'=>'تعذرت الترجمة الآن. يمكنك كتابة الاسم يدويًا والمحاولة لاحقًا.']); }
        });
    }
    /**
     * نطاق المدينة نفسها (لا المحافظة ولا المنطقة الإدارية) من OpenStreetMap، أو null إن لم تُعرف.
     * يعيد: boundary (GeoJSON إن كان للمدينة مضلع خاص بها)، center، bbox [جنوب, غرب, شمال, شرق] على مستوى المدينة، label.
     * معظم مدن المملكة في OSM نقاط فقط، وأقرب مضلع لها هو المحافظة الأوسع منها بكثير؛ لذلك لا يُرسم مضلع المحافظة
     * ويُستخدم مركزها فقط. تُجرَّب الأسماء بالترتيب ثم بدون الأرقام اللاحقة (مثل "Mecca2").
     * النتيجة الناجحة تُخزَّن 30 يومًا والفاشلة ساعة كي لا نُثقل الخدمة العامة.
     */
    public function boundary(?string ...$names): ?array
    {
        $names = array_map(fn ($n) => trim((string) $n), $names);
        $names = array_merge($names, array_map(fn ($n) => trim(preg_replace('/[\s\-_]*[0-9٠-٩]+$/u', '', $n)), $names));
        foreach (array_unique(array_filter($names)) as $name) {
            $key = 'hourly-city-boundary:v2:'.sha1($name);
            if (Cache::has($key)) {
                if ($cached = Cache::get($key)) return $cached;
                continue;
            }
            $result = $this->fetchBoundary($name);
            Cache::put($key, $result ?? false, $result ? now()->addDays(30) : now()->addHour());
            if ($result) return $result;
        }
        return null;
    }

    /** نفس القفل المشترك مع locate()؛ ننتظر قليلًا بدل الفشل الفوري. */
    private function waitForNominatim(): bool
    {
        for ($try = 0; !Cache::add('hourly-nominatim-request', true, 2); $try++) {
            if ($try >= 3) return false;
            usleep(1100000);
        }
        return true;
    }

    /**
     * حدود الحي من OpenStreetMap كحلقة واحدة [خط العرض, خط الطول] صالحة للحفظ عبر DistrictPolygon.
     * تُقبل نتائج مستوى الحي فقط (place_rank ≥ 17) حتى لا تُعاد حدود المدينة أو المحافظة.
     */
    public function districtPolygon(array $names, string ...$cities): array
    {
        $names = array_values(array_unique(array_filter(array_map(fn ($n) => trim((string) $n), $names))));
        $cities = array_values(array_unique(array_filter(array_map(fn ($city) => $this->searchCityName($city), $cities))));
        foreach ($cities as $city) {
            foreach ($names as $name) {
                $key = 'hourly-district-polygon:v2:'.sha1($name.'|'.$city);
                $result = Cache::get($key);
                if ($result === null) {
                    $result = $this->fetchDistrictPolygon($name, $city);
                    if ($result === null) throw ValidationException::withMessages(['polygon'=>'خدمة الخرائط مشغولة. انتظر ثوانٍ ثم أعد المحاولة.']);
                    Cache::put($key, $result, $result ? now()->addDays(30) : now()->addHour());
                }
                if ($result) return $result;
            }
        }
        throw ValidationException::withMessages(['polygon'=>'لم نجد حدود حي صالحة بالأسماء المتاحة في OpenStreetMap؛ قد تكون النتائج نقاطًا أو معالم فقط. يمكنك رسم الحدود يدويًا.']);
    }

    private function searchCityName(string $city): string
    {
        return trim(preg_replace('/[\s\-_]*[0-9٠-٩]+$/u', '', trim($city)));
    }

    /** @return array|false|null مصفوفة عند النجاح، false عند عدم وجود حدود، null عند تعذر الاتصال. */
    private function fetchDistrictPolygon(string $name, string $city)
    {
        if (!$this->waitForNominatim()) return null;
        try {
            $response = Http::withHeaders(['User-Agent'=>'HourlyRentalCityEditor/1.0 ('.config('app.url').')'])->timeout(15)
                ->get(\App\Support\HourlyRentalSettings::get('geocoding_url', 'https://nominatim.openstreetmap.org/search'), [
                    'q'=>$name.', '.$city.', Saudi Arabia', 'countrycodes'=>'sa', 'format'=>'jsonv2',
                    'polygon_geojson'=>1, 'polygon_threshold'=>0.00005, 'limit'=>5,
                ]);
            if (!$response->successful()) return null;
        } catch (\Throwable $e) {
            return null;
        }
        foreach ((array) $response->json() as $row) {
            if (!is_array($row) || (int) ($row['place_rank'] ?? 0) < 17) continue;
            // A park, mountain or building polygon must never be presented as a district boundary.
            $category = $row['category'] ?? $row['class'] ?? null;
            $type = $row['type'] ?? null;
            if (!(($category === 'boundary' && $type === 'administrative')
                || ($category === 'place' && in_array($type, ['suburb','quarter','neighbourhood','city_district','district','borough'], true)))) continue;
            $geometry = $row['geojson'] ?? [];
            $rings = match ($geometry['type'] ?? null) {
                'Polygon' => [$geometry['coordinates'][0] ?? []],
                'MultiPolygon' => array_map(fn ($polygon) => $polygon[0] ?? [], $geometry['coordinates'] ?? []),
                default => [],
            };
            usort($rings, fn ($a, $b) => count($b) <=> count($a));
            $ring = $rings[0] ?? [];
            if (count($ring) < 4) continue;
            if (count($ring) > 1000) {
                $step = (int) ceil(count($ring) / 1000);
                $ring = array_values(array_filter($ring, fn ($i) => $i % $step === 0, ARRAY_FILTER_USE_KEY));
            }
            try {
                $polygon = DistrictPolygon::validate(array_map(fn ($p) => [(float) $p[1], (float) $p[0]], $ring));
            } catch (ValidationException $e) {
                continue;
            }
            return ['polygon'=>$polygon, 'label'=>$row['display_name'] ?? $name];
        }
        return false;
    }

    private function fetchBoundary(string $name): ?array
    {
        if (!$this->waitForNominatim()) return null;
        try {
            $response = Http::withHeaders(['User-Agent'=>'HourlyRentalCityEditor/1.0 ('.config('app.url').')'])->timeout(15)
                ->get(\App\Support\HourlyRentalSettings::get('geocoding_url', 'https://nominatim.openstreetmap.org/search'), [
                    'q'=>$name, 'featuretype'=>'settlement', 'countrycodes'=>'sa', 'format'=>'jsonv2',
                    'polygon_geojson'=>1, 'polygon_threshold'=>0.0005, 'limit'=>10,
                ]);
            if (!$response->successful()) return null;
            $rows = collect($response->json())->filter(fn ($row) => is_array($row) && is_numeric($row['lat'] ?? null) && is_numeric($row['lon'] ?? null));
            $isPolygon = fn ($row) => in_array($row['geojson']['type'] ?? null, ['Polygon', 'MultiPolygon'], true);
            // place_rank: 8 منطقة إدارية، 12 محافظة، 13-18 مدينة/بلدة.
            $cityLevel = $rows->filter(fn ($row) => (int) ($row['place_rank'] ?? 0) >= 13 && (int) $row['place_rank'] <= 18);
            $row = $cityLevel->first($isPolygon) ?? $cityLevel->first();
            if ($row) {
                $bbox = array_map('floatval', (array) ($row['boundingbox'] ?? []));
                return [
                    'boundary'=>$isPolygon($row) ? $row['geojson'] : null,
                    'center'=>[(float) $row['lat'], (float) $row['lon']],
                    'bbox'=>count($bbox) === 4 ? [$bbox[0], $bbox[2], $bbox[1], $bbox[3]] : null,
                    'label'=>$row['display_name'] ?? $name,
                ];
            }
            // لا توجد المدينة نفسها؛ مركز المحافظة يقع في المدينة عادةً، دون رسم حدود المحافظة.
            $row = $rows->first(fn ($row) => (int) ($row['place_rank'] ?? 0) === 12);
            return $row ? ['boundary'=>null, 'center'=>[(float) $row['lat'], (float) $row['lon']], 'bbox'=>null, 'label'=>$row['display_name'] ?? $name] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function locate(string $name, ?string $city = null, bool $addressSearch = false): array
    {
        if ($city !== null) $city = $this->searchCityName($city);
        return Cache::remember('hourly-city-location:'.sha1($name.'|'.$city.($addressSearch ? '|address' : '')),86400,function() use($name,$city,$addressSearch) {
            // Shared, atomic throttle for the public Nominatim endpoint, including simultaneous users.
            if(!Cache::add('hourly-nominatim-request',true,2)) throw ValidationException::withMessages(['name'=>'انتظر ثانيتين ثم أعد المحاولة.']);
            try {
                $query=$city ? ['q'=>$name.', '.$city.', Saudi Arabia'] : ['city'=>$name,'country'=>'Saudi Arabia'];
                if ($addressSearch && !$city) $query = ['q'=>$name.', Saudi Arabia'];
                $response=Http::withHeaders(['User-Agent'=>'HourlyRentalCityEditor/1.0 ('.config('app.url').')'])->timeout(12)->get(\App\Support\HourlyRentalSettings::get('geocoding_url', 'https://nominatim.openstreetmap.org/search'),$query+['countrycodes'=>'sa','format'=>'jsonv2','limit'=>5,'addressdetails'=>1]);
                if(!$response->successful()) throw new \RuntimeException();
                $items=collect($response->json())->filter(fn($row)=>is_array($row) && ($row['address']['country_code'] ?? '')==='sa' && is_numeric($row['lat'] ?? null) && is_numeric($row['lon'] ?? null))->map(fn($row)=>['latitude'=>(float)$row['lat'],'longitude'=>(float)$row['lon'],'label'=>$row['display_name'] ?? $name])->values()->all();
                if(!$items) throw new \RuntimeException();
                return ['results'=>$items];
            } catch (\Throwable $e) { throw ValidationException::withMessages(['name'=>'تعذر العثور على المدينة. عدّل الاسم أو حدد الإحداثيات يدويًا.']); }
        });
    }
}
