<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Settings extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'key',
        'value'
    ];

    public function getSetting($key)
    {
        return @$this->where('key', $key)->first()->value;
    }

    public function setSetting($key, $value)
    {
        $data = $this->where('key', $key)->first();

        if ($data) {
            return $this->where('key', $key)->update(['value' => $value]);
        }
        return $this->create(['value' => $value, 'key' => $key]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
