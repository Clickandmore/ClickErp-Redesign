<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_NOTIFICATION = 'notification';

    protected $fillable = [
        'name',
        'title',
        'channel',
        'sort_order',
        'message',
        'is_active',
        'variables'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'variables' => 'array'
    ];

    public static function channelOptions(): array
    {
        return [
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_WHATSAPP => 'واتساب',
            self::CHANNEL_NOTIFICATION => 'إشعار داخلي',
        ];
    }

    /**
     * Get template by name
     */
    public static function getByName(string $name): ?self
    {
        return self::where('name', $name)->where('is_active', true)->first();
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('title')
            ->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getChannelLabelAttribute(): string
    {
        return self::channelOptions()[$this->channel] ?? strtoupper((string) $this->channel);
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
