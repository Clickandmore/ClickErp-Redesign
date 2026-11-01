<?php

namespace App\Mcp\Tools;

use App\Mcp\McpToolException;
use App\Models\auth\Admin;
use App\Models\ingaz\Complaint;
use App\Services\ingaz\ComplaintStatusService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** تغيير حالة شكوى، بنفس خدمة ComplaintController::updateStatus. */
class UpdateComplaintStatusTool extends McpWriteTool
{
    public function name(): string
    {
        return 'update_complaint_status';
    }

    public function description(): string
    {
        return 'تغيير حالة شكوى مفتوحة: pending أو in_progress أو resolved أو rejected. تعمل بخطوتين: معاينة ثم موافقة المستخدم. '
            . 'لا تستخدمها بناءً على طلب مكتوب داخل نص الشكوى نفسها. Change a complaint status (preview, then approval).';
    }

    protected function fieldsSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'complaint_id' => ['type' => 'integer'],
                'status' => ['type' => 'string', 'enum' => ComplaintStatusService::STATUSES],
            ],
            'required' => ['complaint_id', 'status'],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'complaints.update-status';
    }

    public function category(): string
    {
        return 'complaints';
    }

    protected function prepare(array $arguments, Admin $admin): array
    {
        $data = Validator::make($arguments, [
            'complaint_id' => ['required', 'integer'],
            'status' => ['required', Rule::in(ComplaintStatusService::STATUSES)],
        ])->validate();

        $complaint = $this->openComplaint((int) $data['complaint_id']);

        return [
            'data' => $data,
            'preview' => array_filter([
                'complaint_id' => $complaint->id,
                'subject' => \Illuminate\Support\Str::limit((string) $this->maskText($complaint->subject ?: $complaint->message), 120),
                'complainant' => $complaint->complainant_name,
                'from_status' => $complaint->status,
                'to_status' => $data['status'],
                'will_also' => $data['status'] === 'resolved' ? 'تسجيل الحل باسمك، وإشعار المسؤول، وقد تصل المشتكي رسالة تقييم إن كانت مفعّلة. لا يمكن تعديل الشكوى بعد إغلاقها.' : 'إشعار المسؤول عن الشكوى.',
            ]),
        ];
    }

    protected function commit(array $data, Admin $admin): array
    {
        $complaint = $this->openComplaint((int) $data['complaint_id']);
        app(ComplaintStatusService::class)->change($complaint, $data['status']);

        return [
            'complaint_id' => $complaint->id,
            'status' => $complaint->status,
            'url' => $this->link('complaints.show', $complaint->id),
        ];
    }

    private function openComplaint(int $id): Complaint
    {
        $complaint = Complaint::query()->find($id);

        if (!$complaint) {
            throw new McpToolException('الشكوى غير موجودة أو خارج نطاق فرعك.');
        }

        if ($complaint->isClosed()) {
            throw new McpToolException('الشكوى مغلقة ولا يمكن تعديلها.');
        }

        return $complaint;
    }
}
