<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebsiteVisitorRequestGuard
{
    private const AUTOMATED_USER_AGENT_MARKERS = [
        'bot',
        'crawler',
        'spider',
        'headlesschrome',
        'lighthouse',
        'pagespeed',
        'google-inspectiontool',
        'facebookexternalhit',
        'whatsapp',
        'telegrambot',
        'slackbot',
        'discordbot',
        'curl/',
        'wget/',
        'python-requests',
        'python-urllib',
        'postmanruntime',
        'insomnia/',
        'okhttp/',
        'go-http-client',
        'java/',
        'apache-httpclient',
    ];

    public function ensureHumanBrowser(Request $request): void
    {
        $userAgent = strtolower(trim((string) $request->userAgent()));

        if ($userAgent === '' || Str::contains($userAgent, self::AUTOMATED_USER_AGENT_MARKERS)) {
            abort(403, 'Automated traffic is not tracked.');
        }
    }
}
