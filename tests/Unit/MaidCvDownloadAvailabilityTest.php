<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\MaidController;
use App\Models\ingaz\Maid;
use App\Services\ManagedFileStorage;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class MaidCvDownloadAvailabilityTest extends TestCase
{
    public function test_uploaded_cv_preview_image_is_available_for_pdf_download(): void
    {
        $path = 'images/Maid/uploaded-cv-preview.jpg';
        $maid = new Maid(['cv_preview' => $path]);
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->once()->with($path)->andReturnTrue();
        $this->app->instance(ManagedFileStorage::class, $storage);

        $controller = (new ReflectionClass(MaidController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(MaidController::class))->getMethod('maidDownloadableCvPath');
        $method->setAccessible(true);

        $this->assertSame($path, $method->invoke($controller, $maid));
    }

    public function test_uploaded_cv_preview_pdf_is_available_without_being_generated(): void
    {
        $path = 'images/Maid/uploaded-cv-preview.pdf';
        $maid = new Maid(['cv_preview' => $path]);
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->once()->with($path)->andReturnTrue();
        $this->app->instance(ManagedFileStorage::class, $storage);

        $controller = (new ReflectionClass(MaidController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(MaidController::class))->getMethod('maidDownloadableCvPath');
        $method->setAccessible(true);

        $this->assertSame($path, $method->invoke($controller, $maid));
    }

    public function test_preview_falls_back_to_original_cv_when_generated_key_is_missing(): void
    {
        $generated = 'images/Maid/generated-cvs/missing.svg';
        $original = 'images/Maid/original-cv.png';
        $maid = new Maid(['cv_preview' => $generated, 'cv_file' => $original]);
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('exists')->once()->with($generated)->andReturnFalse();
        $storage->shouldReceive('exists')->once()->with($original)->andReturnTrue();
        $this->app->instance(ManagedFileStorage::class, $storage);

        $controller = (new ReflectionClass(MaidController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(MaidController::class))->getMethod('maidPreviewableCvPath');
        $method->setAccessible(true);

        $this->assertSame($original, $method->invoke($controller, $maid));
    }
}
