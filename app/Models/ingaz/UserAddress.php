<?php

namespace App\Models\ingaz;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UserAddress extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory, LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * تكرار العنوان: نفس العميل والمدينة والاسم والعنوان والحي والمبنى والوحدة، وموقع ضمن ~30 م.
     * المسافات وحالة الأحرف لا تجعل العنوان مختلفًا.
     */
    public static function isDuplicate(array|self $a, array|self $b): bool
    {
        if (self::textKey($a) !== self::textKey($b)) return false;
        $value = fn ($address, string $key) => is_array($address) ? ($address[$key] ?? null) : $address->{$key};
        foreach (['latitude', 'longitude'] as $key) {
            [$x, $y] = [$value($a, $key), $value($b, $key)];
            if (is_numeric($x) !== is_numeric($y) || (is_numeric($x) && abs((float) $x - (float) $y) > 0.0003)) return false;
        }

        return true;
    }

    private static function textKey(array|self $address): string
    {
        $value = fn (string $key) => is_array($address) ? ($address[$key] ?? null) : $address->{$key};
        $text = fn (string $key) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value($key))));

        return implode('|', [(int) $value('user_id'), (int) $value('city_id'), $text('title'), $text('address'), $text('district'), $text('building'), $text('unit')]);
    }

    /** يحفظ العنوان أو يعيد المطابق المحفوظ سابقًا (مع تحديث بيانات التواصل) بدل إنشاء نسخة مكررة. */
    public static function createUnique(array $attributes): self
    {
        $existing = self::where('user_id', $attributes['user_id'])->where('city_id', $attributes['city_id'] ?? null)
            ->orderByDesc('id')->get()->first(fn (self $address) => self::isDuplicate($address, $attributes));
        if (!$existing) return self::create($attributes);

        $contact = array_intersect_key($attributes, array_flip(['name', 'phone', 'contact_phone', 'contact_phone2', 'contact_first_name', 'contact_last_name', 'notes']));
        if (!$existing->hourly_district_id && !empty($attributes['hourly_district_id'])) $contact['hourly_district_id'] = $attributes['hourly_district_id'];
        $existing->fill(array_filter($contact, fn ($v) => $v !== null && $v !== ''))->save();

        return $existing;
    }

    /** يخفي النسخ المكررة القديمة من قوائم الاختيار؛ القائمة مرتبة من الأحدث فيبقى أحدث سجل لكل عنوان. */
    public static function withoutDuplicates(iterable $addresses): \Illuminate\Support\Collection
    {
        return collect($addresses)->reduce(function ($kept, $address) {
            $address = $address instanceof self ? $address : (array) $address;
            return $kept->contains(fn ($other) => self::isDuplicate($other, $address)) ? $kept : $kept->push($address);
        }, collect());
    }
}
