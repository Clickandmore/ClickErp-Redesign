<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\CvOptionsSettingsController;
use App\Http\Requests\ingaz\MaidRequest;
use App\Models\ingaz\Setting;
use App\Support\MaidFormFieldSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * إعداد "حقول نموذج إضافة السيرة" في إعدادات السير الذاتية.
 */
class MaidFormFieldSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        MaidFormFieldSettings::fake(null);
        parent::tearDown();
    }

    public function test_core_fields_are_never_configurable(): void
    {
        $catalog = MaidFormFieldSettings::catalog();

        foreach (array_keys(MaidFormFieldSettings::LOCKED) as $locked) {
            $this->assertArrayNotHasKey($locked, $catalog);
        }

        $states = MaidFormFieldSettings::normalize([
            'nationality_id' => 'hidden',
            'office_id' => 'hidden',
            'unknown_field' => 'hidden',
        ]);

        $this->assertArrayNotHasKey('nationality_id', $states);
        $this->assertArrayNotHasKey('office_id', $states);
        $this->assertArrayNotHasKey('unknown_field', $states);
    }

    public function test_states_outside_a_fields_allowed_list_fall_back_to_default(): void
    {
        $states = MaidFormFieldSettings::normalize([
            'image' => 'hidden',          // الصورة: اختيارية فقط
            'video_link' => 'optional',   // غير إلزامي أصلًا: مخفي فقط
            'height_weight' => 'hidden',
            'id_fields' => 'optional',
        ]);

        $this->assertSame('default', $states['image']);
        $this->assertSame('default', $states['video_link']);
        $this->assertSame('hidden', $states['height_weight']);
        $this->assertSame('optional', $states['id_fields']);
        $this->assertSame('default', $states['skills']);
    }

    public function test_everything_defaults_to_current_behaviour(): void
    {
        MaidFormFieldSettings::fake([]);

        $this->assertSame([], MaidFormFieldSettings::clientConfig());
        foreach (MaidFormFieldSettings::states() as $state) {
            $this->assertSame('default', $state);
        }
    }

    public function test_saving_the_settings_page_persists_only_changed_fields(): void
    {
        Setting::query()->firstOrCreate([]);

        $request = Request::create('/settings/cv-options', 'PUT', [
            'cv_site_sort_order' => 'random',
            'maid_form_fields' => [
                'height_weight' => 'hidden',
                'birth_date' => 'optional',
                'skills' => 'default',
                'nationality_id' => 'hidden',
            ],
        ]);
        app()->instance('request', $request);

        app(CvOptionsSettingsController::class)->update($request);

        $stored = json_decode((string) Setting::query()->value(MaidFormFieldSettings::COLUMN), true);
        $this->assertSame(['birth_date' => 'optional', 'height_weight' => 'hidden'], $this->sorted($stored));

        MaidFormFieldSettings::clearCache();
        $this->assertTrue(MaidFormFieldSettings::isHidden('height_weight'));
        $this->assertTrue(MaidFormFieldSettings::isRelaxed('birth_date'));
        $this->assertFalse(MaidFormFieldSettings::isRelaxed('skills'));
    }

    public function test_cooking_experience_is_required_only_while_the_field_is_on_default(): void
    {
        $data = ['experience_status' => 'has_experience', 'cooking_experience' => null];

        MaidFormFieldSettings::fake([]);
        $this->assertTrue($this->cookingValidator($data)->fails());

        MaidFormFieldSettings::fake(['cooking_experience' => 'optional']);
        $this->assertFalse($this->cookingValidator($data)->fails());

        MaidFormFieldSettings::fake(['cooking_experience' => 'hidden']);
        $this->assertFalse($this->cookingValidator($data)->fails());
    }

    public function test_form_partial_ships_only_changed_fields_to_the_browser(): void
    {
        MaidFormFieldSettings::fake(['height_weight' => 'hidden', 'image' => 'optional']);

        $html = view('admin.ingaz.maids.partials.form-field-config', ['disableHiddenInputs' => true])->render();

        $this->assertStringContainsString('"height_weight":{"state":"hidden"', $html);
        $this->assertStringContainsString('"image":{"state":"optional"', $html);
        $this->assertStringNotContainsString('"skills"', $html);
        $this->assertStringContainsString('var disableHidden = true;', $html);
    }

    public function test_both_maid_forms_load_the_field_config_and_respect_it_on_submit(): void
    {
        foreach (['create', 'edit'] as $form) {
            $source = file_get_contents(resource_path("views/admin/ingaz/maids/{$form}.blade.php"));

            $this->assertStringContainsString("partials.form-field-config", $source, $form);
            $this->assertStringContainsString("maidFormFields.relaxed('image')", $source, $form);
            $this->assertStringContainsString("maidFormFields.relaxed('id_fields')", $source, $form);
            $this->assertStringContainsString("maidFormFields.relaxed('marital_status')", $source, $form);
        }
    }

    public function test_empty_or_zero_age_means_not_entered(): void
    {
        // النموذج يرسل "0" عند ترك العمر فارغًا، وقد يكون فارغًا عند جعل تاريخ الميلاد اختياريًا أو إخفاء العمر.
        foreach (['0', ''] as $age) {
            $request = MaidRequest::create('/maids', 'POST', ['age' => $age]);
            $prepare = new \ReflectionMethod($request, 'prepareForValidation');
            $prepare->setAccessible(true);
            $prepare->invoke($request);

            $this->assertNull($request->input('age'), "age '{$age}'");
            $this->assertFalse(Validator::make($request->all(), ['age' => $request->rules()['age']])->fails());
        }

        $request = MaidRequest::create('/maids', 'POST', ['age' => '25']);
        $prepare = new \ReflectionMethod($request, 'prepareForValidation');
        $prepare->setAccessible(true);
        $prepare->invoke($request);
        $this->assertSame('25', $request->input('age'));
    }

    private function cookingValidator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $request = MaidRequest::create('/maids', 'POST', $data);
        $rules = $request->rules();

        return Validator::make($data, ['cooking_experience' => $rules['cooking_experience']]);
    }

    private function sorted(array $values): array
    {
        ksort($values);

        return $values;
    }
}
