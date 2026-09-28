<?php
namespace App\Models\Hourly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class HourlyPackage extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    public function getSettingsOverridesAttribute($value) { return is_string($value) ? json_decode($value,true) : $value; }
    public function setSettingsOverridesAttribute($value) { $this->attributes['settings_overrides']=$value===null ? null : json_encode($value); }
    protected $casts = ['is_active'=>'boolean','transport_included'=>'boolean','start_times'=>'array','is_private'=>'boolean','materials_included'=>'boolean','for_home'=>'boolean','for_company'=>'boolean','show_on_app'=>'boolean','ignore_closed_days'=>'boolean'];
    protected $appends = ['title'];
    protected $with = ['translations'];
    protected static function booted(): void
    {
        // أي مسار قديم يحفظ اسم الفئة النصي فقط يُربط تلقائيًا بفئة بنفس الاسم، فلا يبقى عنصر بلا فئة.
        // يُفحص الجدول عند كل حفظ (نادر) بدل تخزينه، لأن الاتصال قد يتغير بين العمليات والاختبارات.
        static::saving(function (self $item) {
            if ($item->service_category_id || trim((string) $item->category) === '' || !\Illuminate\Support\Facades\Schema::hasTable('hourly_service_categories')) return;
            $item->service_category_id = app(\App\Services\Hourly\ServiceCategoryService::class)->resolveForItem(null, $item->category)->id;
        });
    }
    public function productAttribute(){return $this->belongsTo(HourlyProductAttribute::class,'attribute_id');}
    public function packageTier() { return $this->belongsTo(HourlyPackageTier::class); }
    public function translations() { return $this->hasMany(HourlyPackageTranslation::class); }
    public function serviceCategory() { return $this->belongsTo(HourlyServiceCategory::class, 'service_category_id')->withTrashed(); }
    public function cities() { return $this->belongsToMany(\App\Models\City::class, 'hourly_package_city'); }
    public function nationalities() { return $this->belongsToMany(\App\Models\ingaz\Nationality::class, 'hourly_package_nationality'); }
    public function getTitleAttribute() { return optional($this->translations->firstWhere('locale', app()->getLocale()) ?? $this->translations->first())->title ?? $this->code; }
    public function effectiveCities()
    {
        $query = \App\Models\City::query()->where('is_active', true);
        if ($this->cities->isNotEmpty()) {
            return $query->whereIn('id', $this->cities->modelKeys());
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('hourly_category_district') || !$this->serviceCategory || !$this->serviceCategory->is_active || $this->serviceCategory->trashed()) {
            return $query->whereRaw('1 = 0');
        }
        return $query->whereIn('id', $this->serviceCategory->districts()
            ->where('hourly_service_districts.is_active', true)->wherePivot('is_active', true)->select('city_id'));
    }

    public function scopeForCity($query, $city)
    {
        return $query->where(function ($scope) use ($city) {
            $scope->whereHas('cities', fn ($q) => $q->where('cities.id', $city)->where('cities.is_active', true));
            if (\Illuminate\Support\Facades\Schema::hasTable('hourly_category_district')) {
                $scope->orWhere(fn ($fallback) => $fallback->whereDoesntHave('cities')
                    ->whereHas('serviceCategory', fn ($category) => $category->where('is_active', true)->whereNull('deleted_at')
                        ->whereHas('districts', fn ($district) => $district->where('city_id', $city)
                            ->where('hourly_service_districts.is_active', true)->where('hourly_category_district.is_active', true)
                            ->whereIn('city_id', \App\Models\City::where('is_active', true)->select('id')))));
            }
        });
    }
}
