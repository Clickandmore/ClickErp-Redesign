<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\RequestsStatusEnum;
use App\Http\Actions\Api\KnownServiceRequestAction as objAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\ingaz\SocialStatusAction;
use App\Http\Controllers\admin\Controller;
use App\Models\KnownServiceRequest as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KnownServiceRequestController extends Controller
{
    public string $folderPath = 'admin.ingaz.KnownServiceRequests';
    public string $mainRoute = 'known-service-requests';

    public function index(
        Request $request,
        NationalityAction $nationalityAction,
        ReligionAction $religionAction,
        SocialStatusAction $socialStatusAction
    ) {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW), 403);

        if ($request->ajax()) {
            $rows = objModel::query()
                ->with(['nationality', 'religion', 'socialStatus'])
                ->when($request->filled('status'), function ($query) use ($request) {
                    $query->where('status', $request->get('status'));
                })
                ->when($request->filled('nationality'), function ($query) use ($request) {
                    $query->where('nationality_id', $request->get('nationality'));
                })
                ->when($request->filled('religion'), function ($query) use ($request) {
                    $query->where('religion_id', $request->get('religion'));
                })
                ->when($request->filled('social_status'), function ($query) use ($request) {
                    $query->where('social_status_id', $request->get('social_status'));
                })
                ->latest();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('client_name', fn($row) => $row->client_name)
                ->editColumn('worker_name', fn($row) => $row->worker_name)
                ->editColumn('nationality', fn($row) => $row->nationality?->title ?? '-')
                ->editColumn('city', fn($row) => $row->city ?? '-')
                ->editColumn('status', function ($row) {
                    if ($row->status === RequestsStatusEnum::Pending->value) {
                        if (!@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT)) {
                            return '<span class="badge bg-warning">' . RequestsStatusEnum::tryFrom($row->status)?->lang() . '</span>';
                        }

                        return '
                            <div class="btn-group" role="group">
                                <button class="btn btn-success btn-sm accept-request" data-id="' . $row->id . '">
                                    <i class="fas fa-check"></i> ' . __('ingaz.approved') . '
                                </button>
                                <button class="btn btn-danger btn-sm reject-request mx-2" data-id="' . $row->id . '">
                                    <i class="fas fa-times"></i> ' . __('ingaz.rejected') . '
                                </button>
                            </div>';
                    }

                    if ($row->status === RequestsStatusEnum::Approved->value) {
                        return '<span class="badge bg-success">' . RequestsStatusEnum::tryFrom($row->status)?->lang() . '</span>';
                    }

                    if ($row->status === RequestsStatusEnum::Rejected->value) {
                        return '<span class="badge bg-danger">' . RequestsStatusEnum::tryFrom($row->status)?->lang() . '</span>';
                    }

                    return '<span class="badge bg-secondary">-</span>';
                })
                ->addColumn('actions', function ($row) {
                    $items = [];

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW)) {
                        $items[] = '<li><a class="dropdown-item" href="' . route('known-service-requests.show', $row->id) . '">
                            <i class="fas fa-eye"></i> Ø¹Ø±Ø¶ Ø§Ù„ØªÙØ§ØµÙŠÙ„
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE)) {
                        $items[] = '<li><button class="dropdown-item delete-order-request" data-id="' . $row->id . '">
                            <i class="fa fa-trash"></i> Ø­Ø°Ù Ø§Ù„Ø·Ù„Ø¨
                        </button></li>';
                    }

                    if ($items === []) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '
                        <div class="dropdown actions-dropdown">
                            <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cogs me-1"></i> Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg">' . implode('', $items) . '</ul>
                        </div>';
                })
                ->escapeColumns([])
                ->make(true);
        }

        $data['dataTableRoute'] = route($this->mainRoute . '.index');
        $data['status'] = RequestsStatusEnum::cases();
        $data['nationalities'] = $nationalityAction->get();
        $data['religions'] = $religionAction->getReligions();
        $data['socialStatuses'] = $socialStatusAction->get();
        $data['count'] = objModel::count();
        $data['approvedCount'] = objModel::where('status', RequestsStatusEnum::Approved->value)->count();
        $data['rejectedCount'] = objModel::where('status', RequestsStatusEnum::Rejected->value)->count();
        $data['pendingCount'] = objModel::where('status', RequestsStatusEnum::Pending->value)->count();

        return view($this->folderPath . '.index', compact('data'));
    }

    public function show($id)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW), 403);

        $knownServiceRequest = objModel::with(['user', 'nationality', 'religion', 'socialStatus'])->findOrFail($id);

        return view($this->folderPath . '.show', compact('knownServiceRequest'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT), 403);

        $knownServiceRequest = objModel::findOrFail($id);

        if ($request->has('status')) {
            $knownServiceRequest->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'status' => $request->status,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ Ø¨ÙŠØ§Ù†Ø§Øª Ù‚Ø§Ø¨Ù„Ø© Ù„Ù„ØªØ­Ø¯ÙŠØ«.',
        ], 422);
    }

    public function destroy($id, objAction $action)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE), 403);

        $action->delete($id);

        return jsonSuccess(['success' => true]);
    }
}