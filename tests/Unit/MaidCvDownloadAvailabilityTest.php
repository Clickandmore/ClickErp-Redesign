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
}
