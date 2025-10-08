<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Nationality extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];
    protected $fillable = [
        'logo',
        'phone_code',
        'price',
        'service_transportation_cost',
        'rental_cost',
        'monthly_salary',
        'is_show',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'service_transportation_cost' => 'decimal:2',
        'rental_cost' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'is_show' => 'boolean',
    ];

    public $translatedAttributes = ['title', 'sub_title', 'description'];
    protected $translationForeignKey = 'nationality_id';
    public $translationModel = NationalitiesTranslation::class;
    protected $with = ['translations'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
