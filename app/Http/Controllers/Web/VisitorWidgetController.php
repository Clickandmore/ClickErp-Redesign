<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVisitorSite;
use Illuminate\Http\Request;

class VisitorWidgetController extends Controller
{
    public function script(Request $request, string $siteKey)
    {
        $site = WebsiteVisitorSite::query()
            ->where('public_key', $siteKey)
            ->where('is_active', true)
            ->firstOrFail();

        $referer = $request->headers->get('referer');
        if ($referer && !$site->allowsOrigin($referer)) {
            abort(403, 'This domain is not allowed to use the visitor widget.');
        }

        $apiBase = url('/api/visitor-widget/' . $site->public_key);
        $frameUrl = route('visitor-widget.frame', ['siteKey' => $site->public_key]);
        $storageKey = 'clickerp_visitor_' . $site->public_key;
        $side = $site->position === 'left' ? 'left' : 'right';

        $javascript = <<<'JS'
(function () {
    'use strict';
    if (window.__clickErpVisitorWidgetLoaded) return;
    window.__clickErpVisitorWidgetLoaded = true;

    var storageKey = __STORAGE_KEY__;
    var apiBase = __API_BASE__;
    var frameBase = __FRAME_URL__;
    var widgetOrigin = new URL(frameBase).origin;
    var token = localStorage.getItem(storageKey);
    if (!token) {
        token = (window.crypto && window.crypto.randomUUID)
            ? window.crypto.randomUUID()
            : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 3 | 8)).toString(16);
            });
        localStorage.setItem(storageKey, token);
    }

    var wrapper = document.createElement('div');
    wrapper.id = 'clickerp-visitor-widget';
    wrapper.style.cssText = 'position:fixed;bottom:16px;__SIDE__:16px;width:76px;height:76px;z-index:2147483000;border:0;background:transparent;transition:width .2s ease,height .2s ease';

    var iframe = document.createElement('iframe');
    iframe.title = 'محادثة خدمة العملاء';
    iframe.src = frameBase + '?token=' + encodeURIComponent(token);
    iframe.allow = 'clipboard-write';
    iframe.style.cssText = 'width:100%;height:100%;border:0;background:transparent;color-scheme:normal';
    wrapper.appendChild(iframe);

    function mount() {
        if (!document.getElementById(wrapper.id)) document.body.appendChild(wrapper);
    }
    if (document.body) mount();
    else document.addEventListener('DOMContentLoaded', mount);

    function payload() {
        return {
            current_url: location.href,
            page_title: document.title,
            referrer: document.referrer || null,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || null,
            screen_width: window.screen ? window.screen.width : null,
            screen_height: window.screen ? window.screen.height : null
        };
    }

    function heartbeat() {
        fetch(apiBase + '/heartbeat', {
            method: 'POST',
            mode: 'cors',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Visitor-Token': token
            },
            body: JSON.stringify(payload())
        }).catch(function () {});
    }

    heartbeat();
    window.setInterval(heartbeat, 30000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') heartbeat();
    });

    var previousUrl = location.href;
    window.setInterval(function () {
        if (location.href !== previousUrl) {
            previousUrl = location.href;
            heartbeat();
        }
    }, 1000);

    window.addEventListener('message', function (event) {
        if (event.origin !== widgetOrigin || !event.data || event.data.type !== 'clickerp-widget-resize') return;
        var open = Boolean(event.data.open);
        wrapper.style.width = open ? 'min(390px, calc(100vw - 24px))' : '76px';
        wrapper.style.height = open ? 'min(600px, calc(100vh - 24px))' : '76px';
    });
})();
JS;

        $javascript = str_replace(
            ['__STORAGE_KEY__', '__API_BASE__', '__FRAME_URL__', '__SIDE__'],
            [json_encode($storageKey), json_encode($apiBase), json_encode($frameUrl), $side],
            $javascript
        );

        return response($javascript)
            ->header('Content-Type', 'application/javascript; charset=UTF-8')
            ->header('Cache-Control', 'no-store, private');
    }

    public function frame(Request $request, string $siteKey)
    {
        $site = WebsiteVisitorSite::query()
            ->where('public_key', $siteKey)
            ->where('is_active', true)
            ->firstOrFail();

        $referer = $request->headers->get('referer');
        if ($referer && !$site->allowsOrigin($referer)) {
            abort(403, 'This domain is not allowed to embed the visitor widget.');
        }

        $validated = $request->validate([
            'token' => ['required', 'uuid'],
        ]);

        return response()->view('widgets.visitor-chat', [
            'site' => $site,
            'token' => $validated['token'],
            'apiBase' => url('/api/visitor-widget/' . $site->public_key),
        ])->header('Cache-Control', 'no-store, private');
    }
}
