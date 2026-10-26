<?php

namespace App\Support;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Package;
use Illuminate\Support\Str;

final class RentalPackageEligibility
{
    public static function matches(?Package $package, ?Maid $maid): bool
    {
        if (!$package || !$maid) return false;
        if (!$package->nationality_id || (int) $package->nationality_id === (int) $maid->nationality_id) return true;

        $maidTerms = self::terms([$maid->nationality?->title, $maid->nationality?->sub_title]);
        $packageTerms = self::terms([$package->nationality?->title, $package->nationality?->sub_title]);
        if (array_intersect($maidTerms, $packageTerms)) return true;

        $title = self::normalize($package->title);
        foreach ($maidTerms as $term) {
            if (Str::contains($title, $term)) return true;
        }
        return false;
    }

    private static function terms(array $values): array
    {
        return collect($values)->map(fn ($value) => self::normalize($value))->filter()->unique()->values()->all();
    }

    private static function normalize(?string $value): string
    {
        return str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], Str::lower(trim((string) $value)));
    }
}
