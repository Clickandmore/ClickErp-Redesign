<?php

namespace Tests\Feature\Models;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Services\ManagedFileStorage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * يثبّت عقد الملفات في الـ API: قيمة "r2://..." الخام يجب ألا تصل إلى الموقع الخارجي،
 * والملفات المحلية يجب أن تبقى بمسارها النسبي كما هي (لا تغيير على البيانات القائمة).
 */
class ApiMediaUrlTest extends TestCase
{
    use DatabaseTransactions;

    public function test_local_paths_pass_through_unchanged(): void
    {
        $this->assertSame('images/Setting/logo.png', apiFileUrl('images/Setting/logo.png'));
    }

    public function test_blank_values_resolve_to_the_fallback(): void
    {
        $this->assertSame('', apiFileUrl(null));
        $this->assertSame('', apiFileUrl(''));
        $this->assertSame('', apiFileUrl('null'));
        $this->assertSame('placeholder.png', apiFileUrl(null, 'placeholder.png'));
    }

    public function test_serialized_nationality_never_exposes_a_raw_r2_pointer(): void
    {
        $nationality = new Nationality();
        $nationality->setRawAttributes([
            'id' => 1,
            'logo' => ManagedFileStorage::R2_PREFIX . 'clickerp/images/Nationality/x.png',
        ], true);

        $data = $nationality->toArray();

        $this->assertStringNotContainsString(ManagedFileStorage::R2_PREFIX, (string) $data['logo']);
        $this->assertStringNotContainsString(ManagedFileStorage::R2_PREFIX, (string) $data['logo_url']);
    }

    public function test_serialized_maid_never_exposes_a_raw_r2_pointer(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes([
            'id' => 1,
            'image' => ManagedFileStorage::R2_PREFIX . 'clickerp/images/Maid/a.png',
            'cv_file' => ManagedFileStorage::R2_PREFIX . 'clickerp/images/Maid/a.pdf',
            'cv_preview' => ManagedFileStorage::R2_PREFIX . 'clickerp/images/Maid/b.pdf',
            'video' => ManagedFileStorage::R2_PREFIX . 'clickerp/images/Maid/c.mp4',
        ], true);

        $data = $maid->toArray();

        foreach (['image', 'cv_file', 'cv_preview', 'video'] as $column) {
            $this->assertStringNotContainsString(
                ManagedFileStorage::R2_PREFIX,
                (string) $data[$column],
                "العمود {$column} ما زال يسرّب مسار R2 الخام"
            );
        }
    }

    public function test_stored_pointer_stays_intact_for_upload_and_delete_flows(): void
    {
        $stored = ManagedFileStorage::R2_PREFIX . 'clickerp/images/Maid/a.pdf';

        $maid = new Maid();
        $maid->setRawAttributes(['id' => 1, 'cv_file' => $stored], true);

        // الحذف والبث والمعاينة تقرأ الخاصية مباشرة — يجب ألا يمسها التحويل.
        $this->assertSame($stored, $maid->cv_file);
        $this->assertSame($stored, $maid->getRawOriginal('cv_file'));
    }
}
