<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WebsiteVisitorSite extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'public_key',
        'allowed_domains',
        'widget_title',
        'welcome_message',
        'primary_color',
        'position',
        'launcher_mode',
        'trigger_selector',
        'is_active',
    ];

    protected $casts = [
        'allowed_domains' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (WebsiteVisitorSite $site): void {
            $site->public_key ??= Str::random(48);
        });
    }

    public function visitors()
    {
        return $this->hasMany(WebsiteVisitor::class, 'website_visitor_site_id');
    }

    public function allowsOrigin(?string $origin): bool
    {
        $host = strtolower((string) parse_url((string) $origin, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        foreach ((array) $this->allowed_domains as $allowed) {
            $allowed = strtolower(trim((string) $allowed));
            if ($allowed === '*') {
                continue;
            }
            if (str_starts_with($allowed, '*.')) {
                $base = substr($allowed, 2);
                if ($host === $base || str_ends_with($host, '.' . $base)) {
                    return true;
                }
            } elseif ($host === $allowed) {
                return true;
            }
        }

        return false;
    }

    public function embedCode(): string
    {
        return '<script async src="' . route('visitor-widget.script', ['siteKey' => $this->public_key]) . '"></script>';
    }
}
