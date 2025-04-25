<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NewSettings extends Model
{
    use HasFactory;

    protected $table = 'new_settings';

    protected $fillable = [
        'key',
        'value'
    ];

    protected $casts = [
        'value' => 'string'
    ];

    public function getSetting($key)
    {
        $setting = $this->where('key', $key)->first();
        return $setting ? $setting->value : null;
    }

    public function setSetting($key, $value)
    {
        try {
            $setting = $this->where('key', $key)->first();

            if ($setting) {
                Log::info("Updating setting: {$key} with value: {$value}");
                $setting->update(['value' => $value]);
            } else {
                Log::info("Creating new setting: {$key} with value: {$value}");
                $this->create([
                    'key' => $key,
                    'value' => $value
                ]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Error setting {$key}: " . $e->getMessage());
            return false;
        }
    }
}
