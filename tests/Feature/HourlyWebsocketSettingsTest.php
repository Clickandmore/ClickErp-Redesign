<?php

namespace Tests\Feature;

use App\Support\{HourlyRentalSettings, HourlyWebsocketSettings};
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

class HourlyWebsocketSettingsTest extends TestCase
{
    use BuildsHourlyCategorySchema;
    protected function setUp(): void { parent::setUp(); $this->setUpHourlyCategorySchema(); }
    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    public function test_secret_is_encrypted_hidden_and_preserved_when_blank(): void
    {
        HourlyWebsocketSettings::persist(HourlyWebsocketSettings::prepare(['enabled'=>true,'key'=>'public-key','app_id'=>'123','secret'=>'private-secret']));
        $this->assertStringNotContainsString('private-secret',DB::table('new_settings')->where('key',HourlyWebsocketSettings::KEY)->value('value'));
        $this->assertArrayNotHasKey('secret',HourlyWebsocketSettings::forForm());
        $this->assertTrue(HourlyWebsocketSettings::forForm()['has_secret']);
        HourlyWebsocketSettings::persist(HourlyWebsocketSettings::prepare(['secret'=>'','port'=>6001,'scheme'=>'http','host'=>'localhost']));
        $this->assertSame('private-secret',HourlyWebsocketSettings::all()['secret']);
        $this->assertSame(6001,HourlyWebsocketSettings::all()['port']);
        HourlyWebsocketSettings::persist(HourlyWebsocketSettings::prepare(['secret'=>'replacement']));
        $this->assertSame('replacement',HourlyWebsocketSettings::all()['secret']);
    }

    public function test_enabled_connection_requires_credentials(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        HourlyWebsocketSettings::prepare(['enabled'=>true]);
    }

    public function test_settings_form_saves_connection_and_keeps_secret_on_later_updates(): void
    {
        $payload=HourlyRentalSettings::defaults();$payload['is_enabled']=true;
        $payload['websocket']=['enabled'=>1,'key'=>'public-key','app_id'=>'123','secret'=>'private-secret','cluster'=>'eu','host'=>'','port'=>443,'scheme'=>'https'];
        $this->withSession(['dev_superadmin_authenticated' => true])->put(route('dev-superadmin.hourly-rental-settings.update'),$payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(app(\App\Services\Hourly\TrackingSocket::class)->enabled());
        $payload['websocket']['secret']='';$payload['websocket']['enabled']=0;
        $this->withSession(['dev_superadmin_authenticated' => true])->put(route('dev-superadmin.hourly-rental-settings.update'),$payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse(app(\App\Services\Hourly\TrackingSocket::class)->enabled());
        $this->assertSame('private-secret',HourlyWebsocketSettings::all()['secret']);
        $html=view('admin.hourly_rental_settings._websocket',['socketSettings'=>HourlyWebsocketSettings::forForm(),'errors'=>new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringNotContainsString('private-secret',$html);
        $this->assertStringContainsString('name="websocket[secret]"',$html);
        $payload['websocket']['secret']='must-not-be-flashed';
        $payload['websocket']['host']='https://invalid.example/path';
        $this->withSession(['dev_superadmin_authenticated' => true])->put(route('dev-superadmin.hourly-rental-settings.update'),$payload)->assertSessionHasErrors('websocket.host');
        $this->assertArrayNotHasKey('websocket',session()->get('_old_input',[]));
        $this->assertSame('private-secret',HourlyWebsocketSettings::all()['secret']);
    }

    public function test_env_config_is_not_used_for_hourly_socket(): void
    {
        config(['hourly_tracking.websocket_enabled'=>true,'hourly_tracking.key'=>'old-key','hourly_tracking.secret'=>'old-secret','hourly_tracking.app_id'=>'old-app']);
        $this->assertFalse(app(\App\Services\Hourly\TrackingSocket::class)->enabled());
    }
}
