<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * نسخة أخرى من النظام مسموح لها بسحب تحديثات هذه النسخة عبر الـ API.
 * يُحفظ هاش المفتاح فقط؛ المفتاح نفسه يظهر مرة واحدة عند الإنشاء.
 */
class SystemUpdateClient extends Model
{
    protected $guarded = [];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /** @return array{0: SystemUpdateClient, 1: string} العميل والمفتاح الصريح */
    public static function issue(string $name): array
    {
        $token = 'su_' . Str::random(40);

        $client = self::create([
            'name' => $name,
            'token_hash' => self::hashToken($token),
            'token_prefix' => substr($token, 0, 10),
            'is_active' => true,
        ]);

        return [$client, $token];
    }

    public static function findActiveByToken(?string $token): ?self
    {
        if (blank($token)) {
            return null;
        }

        return self::query()
            ->where('token_hash', self::hashToken($token))
            ->where('is_active', true)
            ->first();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
