<?php

namespace Tests\Unit;

use App\Services\ManagedFileStorage;
use App\Support\R2StorageSettings;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class LegacyR2ReadFallbackTest extends TestCase
{
    private array $settings;
    private ManagedFileStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = [
            'enabled' => true,
            'endpoint' => 'https://account.r2.cloudflarestorage.com',
            'bucket' => 'clickerp',
            'access_key_id' => 'key',
            'secret_access_key' => 'secret',
            'public_url' => '',
            'root_prefix' => 'clickerp',
            'use_path_style_endpoint' => false,
            'verified_at' => '2026-08-05T12:00:00+03:00',
        ];

        $diskConfig = R2StorageSettings::diskConfig($this->settings);
        $this->setStaticProperty(R2StorageSettings::class, 'cached', $this->settings);
        config(['filesystems.disks.r2_runtime' => $diskConfig]);
        Storage::fake('public');
        Storage::fake('r2_runtime');
        $this->setStaticProperty(
            ManagedFileStorage::class,
            'configuredDiskHash',
            hash('sha256', serialize($diskConfig))
        );

        $this->storage = app(ManagedFileStorage::class);
    }

    protected function tearDown(): void
    {
        $this->setStaticProperty(R2StorageSettings::class, 'cached', null);
        $this->setStaticProperty(ManagedFileStorage::class, 'configuredDiskHash', null);
        Storage::forgetDisk('r2_runtime');

        parent::tearDown();
    }

    public function test_legacy_path_reads_r2_copy_before_local_copy(): void
    {
        $path = 'images/Maid/photo.jpg';
        Storage::disk('public')->put($path, 'local-copy');
        Storage::disk('r2_runtime')->put('clickerp/' . $path, 'r2-copy');

        $this->assertSame('r2-copy', $this->storage->contents($path));
    }

    public function test_legacy_path_falls_back_to_local_when_r2_copy_is_missing(): void
    {
        $path = 'images/Maid/photo.jpg';
        Storage::disk('public')->put($path, 'local-copy');

        $this->assertTrue($this->storage->exists($path));
        $this->assertSame('local-copy', $this->storage->contents($path));
    }

    public function test_explicit_r2_path_reads_r2_without_legacy_remapping(): void
    {
        Storage::disk('r2_runtime')->put('clickerp/images/photo.jpg', 'r2-copy');

        $this->assertSame(
            'r2-copy',
            $this->storage->contents('r2://clickerp/images/photo.jpg')
        );
    }

    public function test_deleting_legacy_path_removes_r2_and_local_copies(): void
    {
        $path = 'images/Maid/photo.jpg';
        Storage::disk('public')->put($path, 'local-copy');
        Storage::disk('r2_runtime')->put('clickerp/' . $path, 'r2-copy');

        $this->assertTrue($this->storage->delete($path));
        Storage::disk('public')->assertMissing($path);
        Storage::disk('r2_runtime')->assertMissing('clickerp/' . $path);
    }

    private function setStaticProperty(string $class, string $property, mixed $value): void
    {
        (new ReflectionClass($class))->getProperty($property)->setValue(null, $value);
    }
}
