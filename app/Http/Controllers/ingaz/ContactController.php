<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\ContactAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\Contact as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ContactController extends Controller
{
    public $folderPath = "admin.ingaz.contact";
    public $mainRoute = "contact";

    public function __construct()
    {
        // $this->middleware('permission:الاتصال-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الاتصال-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الاتصال-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الاتصال-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $filters = [
            'keyword' => trim((string) $request->input('keyword', '')),
            'is_contacted' => $request->input('is_contacted', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];

        if ($request->ajax()) {
            $contacts = $this->applyFilters(objModel::query(), $filters)->latest('created_at');

            return DataTables::eloquent($contacts)
                ->editColumn('name', function ($row) {
                    $initial = Str::upper(Str::substr($row->name, 0, 1));

                    return '
                        <div class="d-flex align-items-center gap-3">
                            <div class="contact-avatar-sm">' . e($initial) . '</div>
                            <div>
                                <div class="fw-bold text-dark">' . e($row->name) . '</div>
                                <small class="text-muted">#' . $row->id . '</small>
                            </div>
                        </div>
                    ';
                })
                ->editColumn('phone', function ($row) {
                    return $row->phone ? getPhoneWithTag($row->phone) : '<span class="text-muted">-</span>';
                })
                ->editColumn('email', function ($row) {
                    return $row->email ? getEmailWithTag($row->email) : '<span class="text-muted">-</span>';
                })
                ->editColumn('subject', function ($row) {
                    $subject = trim((string) $row->subject) ?: 'بدون عنوان';

                    return '<span class="badge rounded-pill bg-light text-dark px-3 py-2 border">' . e(Str::limit($subject, 60)) . '</span>';
                })
                ->editColumn('message', function ($row) {
                    $message = trim((string) $row->message);

                    return '
                        <div class="contact-message-cell" title="' . e($message) . '">
                            ' . e(Str::limit($message, 140)) . '
                        </div>
                    ';
                })
                ->editColumn('is_contacted', function ($row) {
                    if ((int) $row->is_contacted === 1) {
                        return '
                            <span class="badge rounded-pill status-badge status-contacted">
                                <i class="fa fa-check-circle ms-1"></i>
                                تم التواصل
                            </span>
                        ';
                    }

                    return '
                        <button class="btn btn-sm btn-contact-action confirm_contacted" data-id="' . $row->id . '">
                            <i class="fa fa-phone ms-1"></i>
                            تأكيد التواصل
                        </button>
                    ';
                })
                ->editColumn('created_at', function ($row) {
                    $createdAt = optional($row->created_at);

                    if (!$createdAt) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '
                        <div class="text-nowrap fw-semibold">' . e($createdAt->format('Y-m-d')) . '</div>
                        <small class="text-muted">' . e($createdAt->format('h:i A')) . '</small>
                    ';
                })
                ->addColumn('request_type', function () {
                    return '
                        <span class="badge rounded-pill contact-soft-primary px-3 py-2">
                            نموذج اتصل بنا
                        </span>
                    ';
                })
                ->addColumn('actions', function ($row) {
                    if (@AdminGuardHasPerm(\App\PermKeys::CONTACT_DELETE)) {
                        return deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return '<span class="text-muted">-</span>';
                })
                ->escapeColumns([])
                ->make(true);
        }

        $filteredQuery = $this->applyFilters(objModel::query(), $filters);

        return view($this->folderPath . '.index', [
            'dataTableRoute' => route($this->mainRoute . ".index"),
            'filters' => $filters,
            'stats' => [
                'total' => objModel::count(),
                'new' => objModel::where('is_contacted', 0)->count(),
                'contacted' => objModel::where('is_contacted', 1)->count(),
                'today' => objModel::whereDate('created_at', today())->count(),
                'filtered' => (clone $filteredQuery)->count(),
            ],
        ]);
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['keyword'])) {
            $search = $filters['keyword'];

            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($filters['is_contacted'] !== '' && $filters['is_contacted'] !== null) {
            $query->where('is_contacted', $filters['is_contacted']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function destroy($id, ContactAction $action)
    {
        $action->delete($id);

        return jsonSuccess();
    }

    public function update(Request $request, $id, ContactAction $action)
    {
        $row = $action->find($id);
        $row->update(['is_contacted' => 1]);

        return jsonSuccess();
    }
}
