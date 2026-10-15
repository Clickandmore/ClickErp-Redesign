<?php

namespace App\Support;

/**
 * فك ترميز Plus Code (Open Location Code) الذي يعرضه Google Maps، مثل «CWQC+4C9» أو «7GVPCWQC+4C9».
 * الرمز القصير يحتاج نقطة مرجعية قريبة (مركز المدينة) لاستعادة الرمز الكامل.
 */
class PlusCode
{
    private const ALPHABET = '23456789CFGHJMPQRVWX';
    private const SEPARATOR_POSITION = 8;
    private const PAIR_RESOLUTIONS = [20.0, 1.0, 0.05, 0.0025, 0.000125];
    private const GRID_ROWS = 5;
    private const GRID_COLUMNS = 4;

    /** يلتقط أول Plus Code داخل نص حر؛ يعيد [الرمز، بقية النص (العنوان)] أو null. */
    public static function extract(string $text): ?array
    {
        if (!preg_match('/(?<![0-9A-Z])([23456789CFGHJMPQRVWX]{2,8}0*\+[23456789CFGHJMPQRVWX]{0,7})(?![0-9A-Z])/i', $text, $match, PREG_OFFSET_CAPTURE)) return null;
        $code = strtoupper($match[1][0]);
        if (!self::isValid($code)) return null;
        $locality = trim(substr($text, 0, $match[1][1]).' '.substr($text, $match[1][1] + strlen($match[1][0])), " \t\n\r,،");

        return [$code, $locality];
    }

    public static function isValid(string $code): bool
    {
        $code = strtoupper($code);
        $separator = strpos($code, '+');
        if ($separator === false || $separator !== strrpos($code, '+') || $separator > self::SEPARATOR_POSITION || $separator % 2 === 1) return false;
        if (strlen($code) - $separator - 1 === 1) return false;
        $digits = str_replace('0', '', substr($code, 0, $separator));
        foreach (str_split($digits.substr($code, $separator + 1)) as $char) if (strpos(self::ALPHABET, $char) === false) return false;

        return true;
    }

    public static function isFull(string $code): bool
    {
        return self::isValid($code) && strpos($code, '+') === self::SEPARATOR_POSITION;
    }

    /** مركز الخلية لرمز كامل: [lat, lng]. */
    public static function decode(string $code): array
    {
        $code = strtoupper(str_replace('+', '', $code));
        $code = rtrim($code, '0');
        $lat = -90.0;
        $lng = -180.0;
        $latSize = $lngSize = 0.0;
        $pairs = min(strlen($code), 10);
        for ($i = 0; $i < $pairs; $i += 2) {
            $resolution = self::PAIR_RESOLUTIONS[intdiv($i, 2)];
            $lat += strpos(self::ALPHABET, $code[$i]) * $resolution;
            if (isset($code[$i + 1])) $lng += strpos(self::ALPHABET, $code[$i + 1]) * $resolution;
            $latSize = $lngSize = $resolution;
        }
        for ($i = 10; $i < strlen($code); $i++) {
            $index = strpos(self::ALPHABET, $code[$i]);
            $latSize /= self::GRID_ROWS;
            $lngSize /= self::GRID_COLUMNS;
            $lat += intdiv($index, self::GRID_COLUMNS) * $latSize;
            $lng += ($index % self::GRID_COLUMNS) * $lngSize;
        }

        return [round($lat + $latSize / 2, 7), round($lng + $lngSize / 2, 7)];
    }

    /** يستعيد الرمز القصير باستخدام أقرب نقطة مرجعية ثم يفك ترميزه: [lat, lng]. */
    public static function recover(string $code, float $refLat, float $refLng): array
    {
        $code = strtoupper($code);
        if (self::isFull($code)) return self::decode($code);
        $padding = self::SEPARATOR_POSITION - strpos($code, '+');
        $resolution = 20 ** (2 - $padding / 2);
        $half = $resolution / 2;
        [$lat, $lng] = self::decode(substr(self::encodePrefix($refLat, $refLng), 0, $padding).$code);
        if ($refLat + $half < $lat && $lat - $resolution >= -90) $lat -= $resolution;
        elseif ($refLat - $half > $lat && $lat + $resolution <= 90) $lat += $resolution;
        if ($refLng + $half < $lng) $lng -= $resolution;
        elseif ($refLng - $half > $lng) $lng += $resolution;

        return [round($lat, 7), round($lng, 7)];
    }

    /** أول 8 خانات من ترميز النقطة المرجعية (يكفي لاستكمال أي رمز قصير). */
    private static function encodePrefix(float $lat, float $lng): string
    {
        $lat = min(89.9999999, max(-90.0, $lat)) + 90;
        $lng = fmod(fmod($lng + 180, 360) + 360, 360);
        $code = '';
        foreach (array_slice(self::PAIR_RESOLUTIONS, 0, 4) as $resolution) {
            $latDigit = (int) floor($lat / $resolution);
            $lngDigit = (int) floor($lng / $resolution);
            $lat -= $latDigit * $resolution;
            $lng -= $lngDigit * $resolution;
            $code .= self::ALPHABET[$latDigit].self::ALPHABET[$lngDigit];
        }

        return $code;
    }
}
