<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'message',
        'is_active',
        'variables'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array'
    ];

    /**
     * Get template by name
     */
    public static function getByName(string $name): ?self
    {
        return self::where('name', $name)->where('is_active', true)->first();
    }

    /**
     * Replace variables in message
     */
    public function processMessage(array $variables = []): string
    {
        $message = $this->message;

        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }
}
