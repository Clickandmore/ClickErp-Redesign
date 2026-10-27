<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Model;

/** A customer's browser registered for website push; the mobile app's tokens live in user_firebase_tokens. */
class UserWebPushToken extends Model
{
    /** Browsers kept per customer; older registrations are dropped. */
    public const PER_USER = 10;

    protected $guarded = [];

    protected $casts = ['last_seen_at' => 'datetime'];

    /** Binds the browser to this customer, taking it over from whoever used it before. */
    public static function register(int $userId, string $token, ?string $userAgent): void
    {
        static::updateOrCreate(['token' => $token], ['user_id' => $userId, 'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null, 'last_seen_at' => now()]);
        $keep = static::where('user_id', $userId)->latest('last_seen_at')->latest('id')->limit(self::PER_USER)->pluck('id');
        static::where('user_id', $userId)->whereNotIn('id', $keep)->delete();
    }
}
