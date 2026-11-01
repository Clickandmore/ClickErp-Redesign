<?php

namespace App\Http\Controllers;

use App\Mcp\McpAccess;
use App\Mcp\Tools\MaidMediaUploadLinkTool;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\McpAccessToken;
use App\Models\McpAuditLog;
use App\Services\ingaz\MaidMediaUpdater;
use App\Support\BranchQueryContext;
use App\Support\DashboardMaintenanceSettings;
use App\Support\McpSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * صفحة رفع صور العاملة وفيديوها من رابط يرسله المساعد (أداة maid_media_upload_link).
 *
 * الرابط موقّع (signed) ومؤقت، ويحمل رقم العاملة والموظف واتصاله (التوكن)؛ لا يحتاج تسجيل
 * دخول لأن الموظف غالباً يفتحه من جواله داخل المحادثة. لذلك تُعاد كل الفحوص عند الفتح
 * وعند الرفع: MCP مفعّل، الاتصال لم يُلغَ، الموظف مسموح له بالأداة، صلاحية تعديل العاملة،
 * ونطاق فرعه. إلغاء الاتصال أو تغيير كلمة المرور يُبطل الرابط فوراً.
 * الحفظ نفسه هو حفظ نافذة «الصور والفيديو» في شاشة العاملة (MaidMediaUpdater).
 */
class McpMaidUploadController extends Controller
{
    public const LINK_MINUTES = 30;

    private const UPLOAD_FIELDS = ['image', 'video', 'cv_file'];

    /** @return array{upload_url: string, expires_at: string, expires_in_minutes: int, instructions: string} */
    public static function linkFor(Maid $maid, Admin $admin, McpAccessToken $token): array
    {
        $expiresAt = now()->addMinutes(self::LINK_MINUTES);

        return [
            'upload_url' => URL::temporarySignedRoute('mcp.maid-upload.show', $expiresAt, [
                'maid' => $maid->id,
                'admin' => $admin->id,
                'token' => $token->id,
            ]),
            'expires_at' => $expiresAt->toDateTimeString(),
            'expires_in_minutes' => self::LINK_MINUTES,
            'instructions' => 'افتح الرابط وارفع الصورة الشخصية أو الفيديو أو ملف السيرة الأصلي. الرابط لك وحدك ولهذه العاملة فقط.',
        ];
    }

    public function show(Request $request, int $maid)
    {
        [$admin, $record] = $this->authorizeLink($request, $maid);

        return view('admin.mcp.maid-upload', [
            'maid' => $record,
            'admin' => $admin,
            'action' => $request->fullUrl(),
            'expiresAt' => now()->setTimestamp((int) $request->query('expires')),
        ]);
    }

    public function store(Request $request, int $maid, MaidMediaUpdater $updater)
    {
        [$admin, $record] = $this->authorizeLink($request, $maid);

        $rules = array_intersect_key($updater->rules(), array_flip(self::UPLOAD_FIELDS));
        $validated = $request->validate($rules, $updater->messages());

        // فقط حقول الصفحة؛ رابط الفيديو وحذف الفيديو يبقيان في شاشة العاملة.
        $uploads = new Request();
        $uploads->files->replace(array_intersect_key($request->allFiles(), $rules));

        if (!$updater->apply($record, $uploads, $validated)) {
            return $this->respond($request, false, 'اختر صورة أو فيديو أو ملف سيرة أولاً.');
        }

        $this->audit($admin, (int) $request->query('token'), $record, array_keys($uploads->allFiles()), $request->ip());

        return $this->respond($request, true, 'تم رفع الملفات وحفظها في سيرة العاملة.');
    }

    /** الصفحة ترفع بـ XHR لتعرض شريط التقدم فتنتظر JSON؛ بدون JavaScript يعود النموذج العادي للصفحة. */
    private function respond(Request $request, bool $saved, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json($saved ? ['message' => $message] : ['message' => $message, 'errors' => ['media' => [$message]]], $saved ? 200 : 422);
        }

        $redirect = redirect()->to($request->fullUrl());

        return $saved ? $redirect->with('success', $message) : $redirect->withErrors(['media' => $message]);
    }

    /** @return array{0: Admin, 1: Maid} */
    private function authorizeLink(Request $request, int $maidId): array
    {
        abort_unless(McpSettings::enabled(), 404);
        abort_if(DashboardMaintenanceSettings::enabled(), 503);
        abort_unless(\App\Support\McpSubscription::allowsWrite(), 403, \App\Support\McpSubscription::writeBlockedMessage());

        $admin = Admin::query()->find((int) $request->query('admin'));
        $token = McpAccessToken::query()->find((int) $request->query('token'));
        $tool = app(MaidMediaUploadLinkTool::class);

        if (!$admin || ($admin->status ?? 'active') === 'suspended'
            || !$token || (int) $token->admin_id !== (int) $admin->id || !$token->isActive()
            || !app(McpAccess::class)->toolAllowed($admin, $tool) || !$tool->isAllowedFor($admin)) {
            abort(403, 'لم تعد لديك صلاحية رفع ملفات العاملة. اطلب رابطاً جديداً أو راجع الإدارة.');
        }

        // نفس هوية الموظف في الشاشة: عزل الفروع وسجل نشاط السيرة باسمه، دون فتح جلسة دخول.
        Auth::shouldUse('admin');
        Auth::guard('admin')->setUser($admin);
        BranchQueryContext::flush();

        $maid = Maid::query()->find($maidId);
        abort_unless($maid, 404);

        return [$admin, $maid];
    }

    private function audit(Admin $admin, int $tokenId, Maid $maid, array $fields, ?string $ip): void
    {
        try {
            McpAuditLog::query()->create([
                'admin_id' => $admin->id,
                'token_id' => $tokenId,
                'method' => 'upload',
                'tool' => 'maid_media_upload_link',
                'arguments' => ['maid_id' => $maid->id, 'files' => $fields],
                'status' => 'ok',
                'ip' => $ip,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('MCP audit log failed', ['exception' => $e->getMessage()]);
        }
    }
}
