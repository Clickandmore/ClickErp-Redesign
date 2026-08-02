<?php

namespace Tests\Unit;

use App\Services\ManagedFileStorage;
use App\Support\R2StorageSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class LegacyUploadHelperStorageTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setStaticProperty(R2StorageSettings::class, 'cached', null);
        $this->setStaticProperty(ManagedFileStorage::class, 'configuredDiskHash', null);
        Storage::forgetDisk('r2_runtime');

        parent::tearDown();
    }

    public function test_shared_upload_helper_uses_local_storage_when_r2_is_disabled(): void
    {
        Storage::fake('public');
        $this->setStaticProperty(R2StorageSettings::class, 'cached', $this->settings(false));

        $path = uploadFile(
            UploadedFile::fake()->create('logo.png', 20, 'image/png'),
            'images/settings/'
        );

        $this->assertStringStartsWith('images/settings/', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(asset('storage/' . $path), showFile($path));
    }

    public function test_shared_upload_helper_uses_r2_when_settings_are_ready(): void
    {
        $settings = $this->settings(true);
        $diskConfig = R2StorageSettings::diskConfig($settings);

        $this->setStaticProperty(R2StorageSettings::class, 'cached', $settings);
        config(['filesystems.disks.r2_runtime' => $diskConfig]);
        Storage::fake('r2_runtime');
        $this->setStaticProperty(
            ManagedFileStorage::class,
            'configuredDiskHash',
            hash('sha256', serialize($diskConfig))
        );

        $path = uploadFile(
            UploadedFile::fake()->create('logo.png', 20, 'image/png'),
            'images/settings/'
        );
        $objectPath = substr($path, strlen(ManagedFileStorage::R2_PREFIX));

        $this->assertStringStartsWith(ManagedFileStorage::R2_PREFIX, $path);
        Storage::disk('r2_runtime')->assertExists($objectPath);
    }

    public function test_shared_upload_helper_preserves_webp_compression(): void
    {
        Storage::fake('public');
        $this->setStaticProperty(R2StorageSettings::class, 'cached', $this->settings(false));

        $path = uploadFile(
            UploadedFile::fake()->image('logo.png', 40, 40),
            'images/settings/',
            1
        );

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    private function settings(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'endpoint' => $enabled ? 'https://account.r2.cloudflarestorage.com' : '',
            'bucket' => $enabled ? 'clickerp' : '',
            'access_key_id' => $enabled ? 'key' : '',
            'secret_access_key' => $enabled ? 'secret' : '',
            'public_url' => '',
            'root_prefix' => 'clickerp',
            'use_path_style_endpoint' => false,
            'verified_at' => $enabled ? '2026-07-27T12:00:00+03:00' : null,
        ];
    }

    private function setStaticProperty(string $class, string $property, mixed $value): void
    {
        (new ReflectionClass($class))->getProperty($property)->setValue(null, $value);
    }
}
