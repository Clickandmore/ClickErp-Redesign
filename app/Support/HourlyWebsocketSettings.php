<?php

namespace App\Support;

use App\Models\NewSettings;
use Illuminate\Support\Facades\{Crypt, Schema, Validator};

class HourlyWebsocketSettings
{
    public const KEY = 'hourly_websocket';

    public static function all(): array
    {
        $defaults = ['enabled'=>false, 'key'=>'', 'secret'=>'', 'app_id'=>'', 'cluster'=>'mt1', 'host'=>'', 'port'=>443, 'scheme'=>'https'];
        if (!Schema::hasTable('new_settings')) return $defaults;
        $stored = NewSettings::where('key', self::KEY)->value('value');
        if (!$stored) return $defaults;
        return array_replace($defaults, json_decode(Crypt::decryptString($stored), true, 512, JSON_THROW_ON_ERROR));
    }

    public static function forForm(): array
    {
        $settings = self::all();
        $settings['has_secret'] = $settings['secret'] !== '';
        unset($settings['secret']);
        return $settings;
    }

    public static function prepare(array $input): array
    {
        $current = self::all();
        $input = array_intersect_key($input, $current);
        if (empty($input['secret'])) unset($input['secret']);
        $settings = array_replace($current, $input);
        $settings['enabled'] = filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN);
        $required = $settings['enabled'] ? 'required' : 'nullable';
        Validator::make(['websocket'=>$settings], [
            'websocket.enabled'=>'boolean',
            'websocket.key'=>[$required,'string','max:200','regex:/^[A-Za-z0-9_-]+$/'],
            'websocket.secret'=>[$required,'string','max:500'],
            'websocket.app_id'=>[$required,'string','max:100','regex:/^[A-Za-z0-9_-]+$/'],
            'websocket.cluster'=>['required','string','max:50','regex:/^[A-Za-z0-9-]+$/'],
            'websocket.host'=>['nullable','string','max:253','regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/'],
            'websocket.port'=>'required|integer|between:1,65535',
            'websocket.scheme'=>'required|in:https,http',
        ], ['required'=>'الحقل :attribute مطلوب لتفعيل اتصال WebSocket.'])->validate();
        $settings['port'] = (int) $settings['port'];
        return $settings;
    }

    public static function persist(array $settings): void
    {
        NewSettings::updateOrCreate(['key'=>self::KEY], ['value'=>Crypt::encryptString(json_encode($settings, JSON_THROW_ON_ERROR))]);
    }
}
