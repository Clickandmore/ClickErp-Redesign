<?php

namespace Tests\Feature\Security;

use App\Http\Requests\ingaz\API\RenewContractRequest as ApiRenewContractRequest;
use App\Http\Requests\site\RenewContractRequest as SiteRenewContractRequest;
use App\Services\ManagedFileStorage;
use App\Support\R2StorageSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Tests\TestCase;

/**
 * قرص public مخدوم مباشرة عبر /storage — رفع ملف بامتداد php/html/svg كان يعني
 * تنفيذ كود على الخادم أو XSS مخزَّن. هذه الاختبارات تثبّت أن الامتداد يُحدَّد من
 * جهة الخادم وأن نموذج تجديد العقد العام يرفض غير الصور/PDF.
 */
class UploadedFileExtensionSecurityTest extends TestCase
{
    use DatabaseTransactions;

    private const RENEW_FILE_FIELDS = [
        'sponsor_card',
        'worker_residence',
        'first_page_of_the_worker_passport',
        'travel_tickets',
        'visa_printed_in_the_worker_passport',
        'exit_and_re_entry_visa',
        'customer_national_address',
        'worker_information',
        'image_of_bank_transfer',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->setStaticProperty(R2StorageSettings::class, 'cached', [
            'enabled' => false,
            'endpoint' => '',
            'bucket' => '',
            'access_key_id' => '',
            'secret_access_key' => '',
            'public_url' => '',
            'root_prefix' => 'clickerp',
            'use_path_style_endpoint' => false,
            'verified_at' => null,
        ]);
    }

    protected function tearDown(): void
    {
        $this->setStaticProperty(R2StorageSettings::class, 'cached', null);

        parent::tearDown();
    }

    /**
     * @dataProvider dangerousUploads
     */
    public function test_upload_file_rejects_dangerous_extensions(string $name, string $mime): void
    {
        try {
            $path = app(ManagedFileStorage::class)->uploadFile(
                UploadedFile::fake()->create($name, 1, $mime),
                'renew_contracts_files'
            );
            $this->fail('Dangerous upload was stored as: ' . $path);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        $this->assertSame([], Storage::disk('public')->allFiles('renew_contracts_files'));
    }

    public function dangerousUploads(): array
    {
        return [
            'php' => ['shell.php', 'application/x-php'],
            'php disguised as image mime' => ['shell.php', 'image/jpeg'],
            'uppercase PHP' => ['shell.PHP', 'text/plain'],
            'phtml' => ['shell.phtml', 'application/x-php'],
            'phar' => ['shell.phar', 'application/octet-stream'],
            'php7' => ['shell.php7', 'text/plain'],
            'html' => ['xss.html', 'text/html'],
            'svg' => ['xss.svg', 'image/svg+xml'],
            'htaccess' => ['.htaccess', 'text/plain'],
            'no extension, php content' => ['shell', 'application/x-php'],
            'unknown extension, html content' => ['page.weird', 'text/html'],
        ];
    }

    public function test_safe_uploads_keep_a_safe_extension(): void
    {
        $storage = app(ManagedFileStorage::class);

        $jpg = $storage->uploadFile(UploadedFile::fake()->image('a.jpg', 10, 10), 'renew_contracts_files');
        $doubleExt = $storage->uploadFile(UploadedFile::fake()->image('x.php.jpg', 10, 10), 'renew_contracts_files');
        $pdf = $storage->uploadFile(UploadedFile::fake()->create('doc.pdf', 5, 'application/pdf'), 'renew_contracts_files');
        $xlsx = $storage->uploadFile(
            UploadedFile::fake()->create('import.XLSX', 5, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            'imports'
        );
        $voice = $storage->uploadFile(UploadedFile::fake()->create('voice.m4a', 5, 'audio/mp4'), 'voice');
        // بلا امتداد من العميل: يُستنتج من الـ MIME.
        $noExt = $storage->uploadFile(UploadedFile::fake()->create('blob', 5, 'image/png'), 'renew_contracts_files');

        $this->assertStringEndsWith('.jpg', $jpg);
        $this->assertStringEndsWith('.jpg', $doubleExt);
        $this->assertStringNotContainsString('php', $doubleExt);
        $this->assertStringEndsWith('.pdf', $pdf);
        $this->assertStringEndsWith('.xlsx', $xlsx);
        $this->assertStringEndsWith('.m4a', $voice);
        $this->assertStringEndsWith('.png', $noExt);

        foreach ([$jpg, $doubleExt, $pdf, $xlsx, $voice, $noExt] as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    // حتى لا يُرفض ملف عميل عادي بامتداد غير شائع
    public function test_unknown_but_harmless_extension_is_kept(): void
    {
        $path = app(ManagedFileStorage::class)->uploadFile(
            UploadedFile::fake()->create('notes.pages', 5, 'application/zip'),
            'renew_contracts_files'
        );

        $this->assertStringEndsWith('.pages', $path);
    }

    public function test_svg_is_allowed_for_back_office_staff_only(): void
    {
        $storage = app(ManagedFileStorage::class);

        try {
            $storage->uploadFile(UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'), 'sliders');
            $this->fail('A guest SVG upload must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }

        $admin = \App\Models\auth\Admin::factory()
            ->forBranch(\App\Models\Branch::factory()->main()->create())
            ->create();
        $this->actingAs($admin, 'admin');

        $path = $storage->uploadFile(UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'), 'sliders');

        $this->assertStringEndsWith('.svg', $path);
    }

    public function test_global_upload_helper_also_rejects_php(): void
    {
        $this->expectException(ValidationException::class);

        uploadFile(UploadedFile::fake()->create('shell.php', 1, 'application/x-php'), 'renew_contracts_files/');
    }

    public function test_store_file_contents_never_keeps_a_dangerous_extension(): void
    {
        $storage = app(ManagedFileStorage::class);

        $php = $storage->storeFileContents('<?php echo 1;', 'images/Maid/arabia', 'php');
        $png = $storage->storeFileContents(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ), 'signatures', 'png');
        $pngFromBadExt = $storage->storeFileContents(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ), 'images/Maid/arabia', 'phtml');

        $this->assertDoesNotMatchRegularExpression('/\.(php|phtml|html?|svg)$/i', $php);
        $this->assertStringEndsWith('.png', $png);
        $this->assertStringEndsWith('.png', $pngFromBadExt);
    }

    /**
     * @dataProvider renewContractRequests
     */
    public function test_renew_contract_request_rejects_php_and_accepts_images(string $requestClass): void
    {
        $rules = array_intersect_key((new $requestClass())->rules(), array_flip(self::RENEW_FILE_FIELDS));
        $this->assertCount(count(self::RENEW_FILE_FIELDS), $rules);

        $phpPayload = [];
        $okPayload = [];
        foreach (self::RENEW_FILE_FIELDS as $field) {
            $phpPayload[$field] = UploadedFile::fake()->create('shell.php', 1, 'application/x-php');
            $okPayload[$field] = $field === 'travel_tickets'
                ? UploadedFile::fake()->create('ticket.pdf', 10, 'application/pdf')
                : UploadedFile::fake()->image('doc.jpg', 10, 10);
        }

        $failed = Validator::make($phpPayload, $rules);
        $this->assertTrue($failed->fails());
        $this->assertEqualsCanonicalizing(self::RENEW_FILE_FIELDS, array_keys($failed->errors()->toArray()));

        $this->assertTrue(Validator::make(['sponsor_card' => 'not-a-file'], $rules)->errors()->has('sponsor_card'));
        $this->assertTrue(Validator::make([], $rules)->errors()->has('sponsor_card'), 'required semantics kept');
        $this->assertFalse(Validator::make($okPayload, $rules)->fails());
    }

    public function renewContractRequests(): array
    {
        return [
            'api' => [ApiRenewContractRequest::class],
            'site' => [SiteRenewContractRequest::class],
        ];
    }

    public function test_api_renew_contract_endpoint_rejects_php_upload(): void
    {
        $response = $this->postJson('/api/client/renew-contracts', [
            'sponsor_card' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sponsor_card']);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function setStaticProperty(string $class, string $property, mixed $value): void
    {
        (new ReflectionClass($class))->getProperty($property)->setValue(null, $value);
    }
}
