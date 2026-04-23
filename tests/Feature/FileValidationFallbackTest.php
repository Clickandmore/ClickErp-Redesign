<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use LogicException;
use Tests\TestCase;

class FileValidationFallbackTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_mimes_rule_falls_back_to_client_extension_when_guessers_are_unavailable(): void
    {
        $file = $this->makeThrowingUploadedFile('avatar.jpg', 'image/jpeg');

        $validator = Validator::make(
            ['file' => $file],
            ['file' => 'required|file|mimes:jpg,png']
        );

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->all()));
    }

    public function test_image_rule_falls_back_when_guessers_are_unavailable(): void
    {
        $file = $this->makeThrowingUploadedFile('profile.png', 'image/png');

        $validator = Validator::make(
            ['image' => $file],
            ['image' => 'required|image']
        );

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->all()));
    }

    public function test_mimetypes_rule_falls_back_to_client_mime_type_when_guessers_are_unavailable(): void
    {
        $file = $this->makeThrowingUploadedFile('statement.pdf', 'application/pdf');

        $validator = Validator::make(
            ['file' => $file],
            ['file' => 'required|file|mimetypes:application/pdf']
        );

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->all()));
    }

    private function makeThrowingUploadedFile(string $originalName, string $clientMimeType): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'mime-fallback-');
        file_put_contents($path, 'test');
        $this->temporaryFiles[] = $path;

        return new class($path, $originalName, $clientMimeType, null, true) extends UploadedFile {
            public function guessExtension()
            {
                throw new LogicException('Unable to guess the extension because no guessers are available.');
            }

            public function getMimeType()
            {
                throw new LogicException('Unable to guess the MIME type as no guessers are available.');
            }
        };
    }
}
