<?php

namespace App\Services\Hourly;

use Illuminate\Support\Facades\Log;
use Pusher\Pusher;
use App\Support\HourlyWebsocketSettings;

class TrackingSocket
{
    public const CHANNEL = 'private-hourly-gps';
    public const EVENT = 'locations.changed';

    public function enabled(): bool
    {
        $s = HourlyWebsocketSettings::all();
        return $s['enabled'] && $s['key'] && $s['secret'] && $s['app_id'];
    }

    protected function client(): Pusher
    {
        $s = HourlyWebsocketSettings::all();
        $options = ['cluster' => $s['cluster'], 'useTLS' => $s['scheme'] === 'https', 'timeout' => 2];
        if ($s['host']) {
            $options += ['host' => $s['host'], 'port' => $s['port'], 'scheme' => $s['scheme']];
        }
        return new Pusher($s['key'], $s['secret'], $s['app_id'], $options);
    }

    public function authorize(string $socketId): string
    {
        return $this->client()->authorizeChannel(self::CHANNEL, $socketId);
    }

    public function changed(): void
    {
        if (!$this->enabled()) return;
        try {
            // Invalidation only: coordinates stay behind the existing authenticated data endpoint.
            $this->client()->trigger(self::CHANNEL, self::EVENT, ['changed' => true]);
        } catch (\Throwable $e) {
            // Never reject a saved GPS fix because the realtime transport is unavailable.
            Log::warning('Hourly GPS WebSocket unavailable; polling remains active.', ['exception' => get_class($e)]);
        }
    }

    public function browserConfig(): ?array
    {
        if (!$this->enabled()) return null;
        $s = HourlyWebsocketSettings::all();
        return ['key' => $s['key'], 'cluster' => $s['cluster'],
            'host' => $s['host'], 'port' => $s['port'],
            'tls' => $s['scheme'] === 'https', 'channel' => self::CHANNEL,
            'event' => self::EVENT, 'authEndpoint' => route('admin.hourly-gps.socket-auth')];
    }
}
