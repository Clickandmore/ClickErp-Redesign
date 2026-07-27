<?php

namespace Tests\Unit;

use App\Services\ManagedFileStorage;
use App\Support\R2StorageSettings;
use PHPUnit\Framework\TestCase;

class R2StorageSettingsTest extends TestCase
{
    public function test_r2_is_only_ready_when_enabled_configured_and_verified(): void
    {
        $settings = [
            'enabled' => true,
            'verified_at' => '2026-07-27T12:00:00+03:00',
            'endpoint' => 'https://account.r2.cloudflarestorage.com',
            'bucket' => 'clickerp',
            'access_key_id' => 'key',
            'secret_access_key' => 'secret',
        ];

        $this->assertTrue(R2StorageSettings::isReady($settings));

        $settings['verified_at'] = null;
        $this->assertFalse(R2StorageSettings::isReady($settings));
    }

    public function test_disk_config_is_compatible_with_cloudflare_r2(): void
    {
        $config = R2StorageSettings::diskConfig([
            'endpoint' => 'https://account.r2.cloudflarestorage.com/',
            'bucket' => 'clickerp',
            'access_key_id' => 'key',
            'secret_access_key' => 'secret',
            'public_url' => 'https://files.example.com/',
            'use_path_style_endpoint' => false,
        ]);

        $this->assertSame('s3', $config['driver']);
        $this->assertSame('auto', $config['region']);
        $this->assertSame('https://account.r2.cloudflarestorage.com', $config['endpoint']);
        $this->assertSame('https://files.example.com', $config['url']);
    }

    public function test_r2_paths_are_distinguishable_from_legacy_local_paths(): void
    {
        $storage = new ManagedFileStorage();

        $this->assertTrue($storage->isR2Path('r2://clickerp/images/Maid/cv.pdf'));
        $this->assertFalse($storage->isR2Path('images/Maid/cv.pdf'));
    }
}
