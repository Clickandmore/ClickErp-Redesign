<?php

namespace App\Mcp\Tools;

use App\Http\Controllers\McpMaidUploadController;
use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\McpAccessToken;

/**
 * رابط رفع مؤقت لصور العاملة وفيديوها.
 *
 * المساعد يرى الصورة في المحادثة لكنه لا يملك الملف نفسه ليرسله عبر MCP، فبدلاً من
 * ذلك يعطي الموظف رابطاً يرفع منه الملفات بجودتها الأصلية. الرابط موقّع ومرتبط
 * بالموظف وبهذه العاملة فقط، وتُعاد فحوص الصلاحية عند فتحه وعند الرفع.
 */
class MaidMediaUploadLinkTool extends McpTool
{
    public function name(): string
    {
        return 'maid_media_upload_link';
    }

    public function description(): string
    {
        return 'رابط مؤقت (' . McpMaidUploadController::LINK_MINUTES . ' دقيقة) لرفع الصورة الشخصية للعاملة وفيديوها وملف سيرتها الأصلي. '
            . 'استخدمه عندما يرسل المستخدم صورة العاملة أو فيديو أو يطلب إضافتهما: أنت لا تستطيع رفع الملفات بنفسك، '
            . 'فأعطه الرابط ليفتحه ويرفع منه. لا يعدّل شيئاً بذاته. '
            . 'Temporary link for the user to upload a maid photo, video or original CV file.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'maid_id' => ['type' => 'integer', 'description' => 'رقم العاملة (id) من search_maids أو من نتيجة create_maid_cv'],
            ],
            'required' => ['maid_id'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'maids.media.update';
    }

    public function isWriteTool(): bool
    {
        return true;
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $maid = Maid::query()->find((int) ($arguments['maid_id'] ?? 0));

        if (!$maid) {
            throw new McpToolException('العاملة غير موجودة أو خارج نطاق فرعك.');
        }

        $token = request()->attributes->get('mcp_token');
        if (!$token instanceof McpAccessToken) {
            throw new McpToolException('تعذر تحديد الاتصال الحالي.');
        }

        return [
            'maid' => ['id' => $maid->id, 'name' => $maid->name, 'cv_code' => $maid->cv_code],
        ] + McpMaidUploadController::linkFor($maid, $admin, $token);
    }
}
