<?php

namespace App\Mcp\Tools;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\McpMaidUploadController;
use App\Http\Requests\StoreRapidCv;
use App\Mcp\McpAccess;
use App\Models\auth\Admin;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\McpAccessToken;
use App\Models\RecruitmentOffices;
use App\Services\ingaz\MaidCvPdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * إضافة سيرة ذاتية (عاملة) بنفس نموذج «السيرة السريعة» في الشاشة: قواعد StoreRapidCv
 * وحفظ MaidAction::createRapidCv، ثم توليد ملف السيرة إن كان القالب مفعّلاً.
 * المساعد يقرأ صورة السيرة في المحادثة ويرسل الحقول؛ الصورة والفيديو يُرفعان من رابط
 * الرفع المؤقت الذي يرجع مع النتيجة (أداة maid_media_upload_link).
 */
class CreateMaidCvTool extends McpWriteTool
{
    public function name(): string
    {
        return 'create_maid_cv';
    }

    public function description(): string
    {
        return 'إضافة سيرة ذاتية لعاملة بخطوتين: الاستدعاء الأول يتحقق ويرجع معاينة ورمز تأكيد دون حفظ، وبعد موافقة المستخدم '
            . 'الصريحة يُعاد الاستدعاء مع confirmation_token فتُحفظ. استخرج الحقول من صورة السيرة أو الجواز إن أرسلها المستخدم، '
            . 'وخذ المعرّفات (الجنسية، الديانة، نوع الخدمة، المهنة، المكتب) من form_options (form=maid) ولا تخمّنها. '
            . 'يرفض رقم جواز مسجل مسبقاً. بعد الحفظ ترجع النتيجة رابط رفع مؤقتاً (media_upload.upload_url): أعطه للمستخدم '
            . 'ليرفع منه صورة العاملة وفيديوها، فأنت لا تستطيع رفع الملفات بنفسك. '
            . 'Create a maid CV: preview first, then confirm with the token.';
    }

    protected function fieldsSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'اسم العاملة بالإنجليزية كما في الجواز (حروف إنجليزية ومسافات فقط)'],
                'passport_num' => ['type' => 'string', 'description' => 'رقم الجواز (حروف إنجليزية وأرقام)'],
                'gender' => ['type' => 'string', 'enum' => ['female', 'male'], 'default' => 'female'],
                'nationality_id' => ['type' => 'integer', 'description' => 'form_options: nationalities'],
                'religion_id' => ['type' => 'integer', 'description' => 'form_options: religions'],
                'type_id' => ['type' => 'integer', 'description' => 'نوع الخدمة — form_options: job_types (استقدام/إيجار/...)'],
                'job_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 1, 'description' => 'المهنة أو المهن — form_options: job_titles'],
                'office_id' => ['type' => 'integer', 'description' => 'مكتب الاستقدام الخارجي — form_options: offices'],
                'is_show' => ['type' => 'boolean', 'default' => true, 'description' => 'إظهار السيرة في الموقع'],
            ],
            'required' => ['passport_num', 'gender', 'nationality_id', 'religion_id', 'type_id', 'job_ids', 'office_id'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'maids.create';
    }

    protected function prepare(array $arguments, Admin $admin): array
    {
        $input = array_filter([
            'name:en' => isset($arguments['name']) ? trim((string) $arguments['name']) : null,
            'passport_num' => isset($arguments['passport_num']) ? strtoupper(trim((string) $arguments['passport_num'])) : null,
            'gender' => $arguments['gender'] ?? 'female',
            'nationality_id' => $arguments['nationality_id'] ?? null,
            'religion_id' => $arguments['religion_id'] ?? null,
            'type_id' => $arguments['type_id'] ?? null,
            'job_id' => isset($arguments['job_ids']) ? array_values((array) $arguments['job_ids']) : null,
            'office_id' => $arguments['office_id'] ?? null,
            'is_show' => array_key_exists('is_show', $arguments) ? (bool) $arguments['is_show'] : null,
        ], fn ($value) => $value !== null && $value !== '');

        $form = new StoreRapidCv();
        $data = Validator::make($input, $form->rules(), $form->messages())->validate();

        return [
            'data' => ['validated' => $data, 'input' => $input],
            'preview' => array_filter([
                'name' => $data['name:en'] ?? null,
                'passport_num' => $data['passport_num'],
                'gender' => $data['gender'] === 'male' ? 'ذكر' : 'أنثى',
                'nationality' => $this->titleOf(Nationality::class, $data['nationality_id']),
                'religion' => $this->titleOf(Religion::class, $data['religion_id']),
                'service_type' => $this->titleOf(JobType::class, $data['type_id']),
                'job_titles' => collect($data['job_id'])->map(fn ($id) => $this->titleOf(JobTitle::class, $id))->filter()->values()->all(),
                'office' => $this->titleOf(RecruitmentOffices::class, $data['office_id']),
                'shown_on_site' => ($data['is_show'] ?? true) ? 'نعم' : 'لا',
                'status_after_save' => 'متاحة',
            ], fn ($value) => $value !== null && $value !== []),
        ];
    }

    protected function commit(array $data, Admin $admin): array
    {
        // MaidAction يقرأ type_id وjob_id من الطلب لربط نوع الخدمة والمهن، كما في الشاشة.
        $request = Request::create('/', 'POST', $data['input']);
        $maid = app(MaidAction::class)->createRapidCv($data['validated'], $request, (int) $admin->id);
        app(MaidCvPdfGenerator::class)->generateIfEnabled($maid);

        return [
            'maid' => [
                'id' => $maid->id,
                'name' => $maid->name,
                'passport_num' => $this->mask($maid->passport_num),
                'cv_code' => $maid->cv_code,
                'url' => route('maids.show', $maid->id),
            ],
        ] + $this->mediaUpload($maid, $admin);
    }

    /** رابط رفع الصورة والفيديو إن كان الموظف يملك أداة الرابط؛ وإلا يُكمل من شاشة العاملة. */
    private function mediaUpload(Maid $maid, Admin $admin): array
    {
        $token = request()->attributes->get('mcp_token');
        $linkTool = app(MaidMediaUploadLinkTool::class);

        if ($token instanceof McpAccessToken
            && app(McpAccess::class)->toolAllowed($admin, $linkTool)
            && $linkTool->isAllowedFor($admin)) {
            return ['media_upload' => McpMaidUploadController::linkFor($maid, $admin, $token)];
        }

        return ['next_step' => 'أضف الصورة الشخصية والفيديو من شاشة العاملة إن لزم.'];
    }
}
