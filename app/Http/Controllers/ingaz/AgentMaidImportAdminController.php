<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\AgentMaidBulkImport;
use App\Services\ingaz\AgentBulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentMaidImportAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = AgentMaidBulkImport::query()
            ->with(['office', 'approvedBy'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->input('office_id'));
        }

        $imports = $query->paginate(15)->appends($request->query());

        return view('admin.ingaz.maids.agent_imports.index', compact('imports'));
    }

    public function show(AgentMaidBulkImport $agentMaidBulkImport)
    {
        $agentMaidBulkImport->load(['rows.maid', 'office', 'approvedBy']);

        return view('admin.ingaz.maids.agent_imports.show', [
            'import' => $agentMaidBulkImport,
        ]);
    }

    public function approve(AgentMaidBulkImport $agentMaidBulkImport, AgentBulkImportService $service, MaidAction $maidAction)
    {
        if (!$agentMaidBulkImport->can_approve) {
            return back()->with('error', 'لا يمكن اعتماد هذا الاستيراد في حالته الحالية.');
        }

        $result = $service->approveImport($agentMaidBulkImport, $maidAction, auth('admin')->id());

        return redirect()
            ->route('admin.agent-imports.show', $agentMaidBulkImport)
            ->with('success', "تم اعتماد {$result['imported']} سجل بنجاح، وتعذر استيراد {$result['failed']} سجل.");
    }

    public function reject(Request $request, AgentMaidBulkImport $agentMaidBulkImport, AgentBulkImportService $service)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'يرجى كتابة سبب الرفض.',
        ]);

        $service->rejectImport($agentMaidBulkImport, auth('admin')->id(), $request->rejection_reason);

        return redirect()
            ->route('admin.agent-imports.index')
            ->with('success', 'تم رفض الاستيراد وإخطار المكتب.');
    }

    public function download(AgentMaidBulkImport $agentMaidBulkImport)
    {
        $path = $agentMaidBulkImport->uploaded_file_path;

        if (!$path || !Storage::exists($path)) {
            abort(404, 'الملف غير موجود.');
        }

        return Storage::download($path, $agentMaidBulkImport->original_filename);
    }
}
