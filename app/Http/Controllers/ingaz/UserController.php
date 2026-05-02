<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\UserAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\ingaz\ClientAttachment;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\Order;
use App\Models\ingaz\User as objModel;
use App\Models\RentalContract;
use App\Models\SponsorshipTransfer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Client;
use Nami\Finance\Models\ClientCategory;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{

    public $folderPath = "admin.ingaz.clients";
    public $postData = ["is_blocked"];
    public $mainRoute = "clients";

    function __construct()
    {
        // $this->middleware('permission:العملاء-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:العملاء-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:العملاء-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:العملاء-حذف,admin', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $units = objModel::query()
                ->with(['nationality'])
                ->withCount([
                    'orders as orders_count',
                    'orders as active_orders_count' => function ($query) {
                        $query->whereNotIn('status', ['completed', 'done', 'refused', 'return']);
                    },
                    'rentalContracts as rental_contracts_count',
                    'sponsorshipTransfersAsNewSponsor as transfer_requests_count',
                    'clientAttachments as client_attachments_count',
                ])
                ->addSelect([
                    'last_order_at' => Order::select('created_at')
                        ->whereColumn('user_id', 'users.id')
                        ->latest('created_at')
                        ->limit(1),
                    'last_rental_contract_at' => RentalContract::select('created_at')
                        ->whereColumn('user_id', 'users.id')
                        ->latest('created_at')
                        ->limit(1),
                    'last_transfer_request_at' => SponsorshipTransfer::select('created_at')
                        ->whereColumn('new_sponsor_id', 'users.id')
                        ->latest('created_at')
                        ->limit(1),
                ])
                ->orderByDesc('id');

            if ($request->filled('status')) {
                $status = $request->get('status');
                if ($status === 'active') {
                    $units->where('is_blocked', 0);
                } elseif ($status === 'blocked') {
                    $units->where('is_blocked', 1);
                }
            }

            if ($request->filled('nationality_id')) {
                $units->where('nationality_id', $request->get('nationality_id'));
            }

            if ($request->filled('city')) {
                $units->where('city', $request->get('city'));
            }

            if ($request->filled('has_orders')) {
                $hasOrders = $request->get('has_orders');
                if ($hasOrders === 'with') {
                    $units->whereHas('orders');
                } elseif ($hasOrders === 'without') {
                    $units->doesntHave('orders');
                }
            }

            if ($request->filled('date_from')) {
                $from = Carbon::parse($request->get('date_from'))->startOfDay();
                $units->whereDate('created_at', '>=', $from);
            }

            if ($request->filled('date_to')) {
                $to = Carbon::parse($request->get('date_to'))->endOfDay();
                $units->whereDate('created_at', '<=', $to);
            }

            if ($request->filled('keyword')) {
                $keyword = trim($request->get('keyword'));
                $units->where(function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('email', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%')
                        ->orWhere('phone2', 'like', '%' . $keyword . '%')
                        ->orWhere('national_num', 'like', '%' . $keyword . '%');
                });
            }

            return DataTables::of($units)
                ->addIndexColumn()
                ->addColumn('bulk_select', function ($row) {
                    if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_TOGGLE_STATUS)) {
                        return '';
                    }

                    return '<div class="form-check m-0 d-flex justify-content-center">
                                <input class="form-check-input client-bulk-checkbox" type="checkbox"
                                    value="' . (int) $row->id . '"
                                    data-name="' . e($row->name) . '"
                                    data-status="' . (int) $row->is_blocked . '">
                            </div>';
                })
                ->editColumn('name', function ($row) {

                    $badge = $row->is_blocked == 0
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle">' . helperTrans('ingaz.active') . '</span>'
                        : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">' . helperTrans('ingaz.blocked') . '</span>';

                    return '<div class="d-flex flex-column gap-1">
                        <div class="d-flex flex-column gap-2 name-column">
                            <span class="fw-semibold text-dark name">' . e($row->name) . '</span>
                            <div> ' . $badge . ' </div>
                        </div>

                    </div>';
                })
                ->editColumn('is_blocked', function ($row) {
                    if ($row->is_blocked == 0) {
                        return '<span class="badge bg-success rounded-pill px-3">' . helperTrans('ingaz.active') . '</span>';
                    }
                    return '<span class="badge bg-danger rounded-pill px-3">' . helperTrans('ingaz.blocked') . '</span>';
                })
                ->editColumn('account_id', function ($row) {
                    if ($row->account_id != null) {
                        return '<span class="badge bg-success rounded-pill px-3">' . helperTrans('ingaz.yes') . '</span>';
                    } else {
                        return '<span class="badge bg-danger rounded-pill px-3">' . helperTrans('ingaz.no') . '</span>';
                    }
                })
                ->editColumn('client_data', function ($row) {
                    $nationality = $row->nationality ? $row->nationality->title : helperTrans('ingaz.not_specified');
                    $city = $row->city ?: helperTrans('ingaz.not_specified');
                    $idNumber = $row->national_num ? '<span class="badge bg-light text-dark border me-1"><i class="fas fa-id-card me-1"></i>' . e($row->national_num) . '</span>' : '';

                    $primary = $row->phone
                        ? '<a href="tel:' . e($row->phone_code . $row->phone) . '" class="text-decoration-none">
                                <i class="fas fa-phone-alt me-1 text-success"></i>' . e($row->phone_code . $row->phone) . '
                            </a>'
                        : '<span class="text-muted">' . helperTrans('ingaz.not_specified') . '</span>';

                    $secondary = $row->phone2
                        ? '<a href="tel:' . e($row->phone2) . '" class="text-decoration-none">
                                <i class="fas fa-phone me-1 text-muted"></i>' . e($row->phone2) . '
                            </a>'
                        : '';

                    $email = $row->email
                        ? '<a href="mailto:' . e($row->email) . '" class="text-decoration-none">
                                <i class="fas fa-envelope me-1 text-primary"></i>' . e($row->email) . '
                            </a>'
                        : '';

                    $data = '<div class="small text-muted">
                            <i class="fas fa-flag me-1"></i>' . e($nationality) . '
                            <span class="mx-2">•</span>
                            <i class="fas fa-city me-1"></i>' . e($city) . '
                        </div>
                        <div>' . $idNumber . '</div>';

                    return '<div class="d-flex flex-column gap-1 client-data">
                                <div class="phone">' . $primary . '</div>
                                ' . ($secondary ? '<div class="phone">' . $secondary . '</div>' : '') . '
                                ' . ($email ? '<div class="email">' . $email . '</div>' : '') . '
                                ' . '<div class="data">' . $data . '</div>'  . '
                            </div>';
                })
                ->editColumn('created_at', function ($row) {
                    if (!isset($row->created_at)) {
                        return '-';
                    }

                    $createdAt = $row->created_at->format('Y-m-d');
                    $human = $row->created_at->diffForHumans();

                    return '<div class="d-flex flex-column">
                                <span class="fw-semibold">' . $createdAt . '</span>
                                <span class="text-muted small">' . $human . '</span>
                            </div>';
                })
                ->addColumn('metrics', function ($row) {
                    $ordersCount = number_format((int)$row->orders_count);
                    $activeOrders = number_format((int)$row->active_orders_count);
                    $rentalCount = number_format((int)$row->rental_contracts_count);
                    $transferCount = number_format((int)$row->transfer_requests_count);

                    return '<div class="d-flex flex-column gap-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="fas fa-clipboard-list me-1"></i>' . helperTrans('ingaz.total_orders_count') . ': ' . $ordersCount . '
                                    </span>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <i class="fas fa-tasks me-1"></i>' . helperTrans('ingaz.active_orders_count') . ': ' . $activeOrders . '
                                    </span>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="fas fa-file-contract me-1"></i>' . helperTrans('ingaz.rental_contracts_count') . ': ' . $rentalCount . '
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <i class="fas fa-exchange-alt me-1"></i>' . helperTrans('ingaz.transfer_requests_count') . ': ' . $transferCount . '
                                    </span>
                                </div>

                            </div>';
                })
                ->addColumn('last_activity', function ($row) {
                    $lastOrder = $row->last_order_at ? Carbon::parse($row->last_order_at)->diffForHumans() : helperTrans('ingaz.not_specified');
                    $lastRental = $row->last_rental_contract_at ? Carbon::parse($row->last_rental_contract_at)->diffForHumans() : helperTrans('ingaz.not_specified');
                    $lastTransfer = $row->last_transfer_request_at ? Carbon::parse($row->last_transfer_request_at)->diffForHumans() : helperTrans('ingaz.not_specified');

                    return '<div class="d-flex flex-column gap-2">
                                <div class="small text-muted last-activity">
                                    <div><i class="fas fa-history me-1"></i>' . helperTrans('ingaz.last_order_activity') . ': <span>' . $lastOrder . '</span> </div>
                                    <div><i class="fas fa-handshake me-1"></i>' . helperTrans('ingaz.last_rental_activity') . ': <span>' . $lastRental . '</span></div>
                                    <div><i class="fas fa-random me-1"></i>' . helperTrans('ingaz.last_transfer_activity') . ': <span>' . $lastTransfer . '</span></div>
                                </div>
                            </div>';
                })
                ->addColumn('actions', function ($row) {
                    $menuItems = '';
                    $hasManagementDivider = false;

                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW)) {
                        $menuItems .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.show', $row->id) . '">
                            <i class="fas fa-eye me-2 text-primary"></i>' . helperTrans('ingaz.view_details') . '
                        </a></li>';

                        $menuItems .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.show', $row->id) . '#client-attachments">
                            <i class="fas fa-paperclip me-2 text-info"></i>مرفقات العميل
                            <span class="badge bg-light text-dark ms-1">' . number_format((int) $row->client_attachments_count) . '</span>
                        </a></li>';

                        if ((int) $row->client_attachments_count > 0) {
                            $menuItems .= '<li><a class="dropdown-item" href="' . route('clients.attachments.download', $row->id) . '">
                                <i class="fas fa-download me-2 text-success"></i>تحميل مرفقات العميل
                            </a></li>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE_RECRUITMENT)) {
                        $menuItems .= '<li><a class="dropdown-item" href="' . route('admin.recruitment.select.maid', ['client_id' => $row->id]) . '">
                            <i class="fas fa-user-plus me-2 text-success"></i>' . helperTrans('ingaz.create_recruitment_request') . '
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::RENT_CONTRACT_CREATE)) {
                        $menuItems .= '<li><a class="dropdown-item" href="' . route('RentContract.create', ['client_id' => $row->id]) . '">
                            <i class="fas fa-file-contract me-2 text-info"></i>' . helperTrans('ingaz.create_rent_contract') . '
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CREATE)) {
                        $menuItems .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.create', ['new_sponsor_id' => $row->id]) . '">
                            <i class="fas fa-random me-2 text-warning"></i>' . helperTrans('ingaz.create_transfer_request') . '
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
                        if ($menuItems !== '' && !$hasManagementDivider) {
                            $menuItems .= '<li><hr class="dropdown-divider"></li>';
                            $hasManagementDivider = true;
                        }
                        $menuItems .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '">
                            <i class="fas fa-edit me-2 text-secondary"></i>' . helperTrans('ingaz.edit_client') . '
                        </a></li>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_DELETE)) {
                        if ($menuItems !== '' && !$hasManagementDivider) {
                            $menuItems .= '<li><hr class="dropdown-divider"></li>';
                            $hasManagementDivider = true;
                        }
                        $menuItems .= '<li><div class="dropdown-item p-0">' . deleteButton(route($this->mainRoute . ".destroy", $row->id)) . '</div></li>';
                    }

                    return '<div class="dropdown actions-dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false">
                                    <i class="fas fa-cogs me-1"></i>' . helperTrans('ingaz.actions_menu') . '
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                    ' . $menuItems . '
                                </ul>
                            </div>';
                })
                ->rawColumns(['bulk_select', 'name', 'actions', 'is_blocked', 'account_id', 'client_data', 'created_at', 'metrics', 'last_activity'])
                ->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("auth.clients");
        $data["addButtonText"] = __("auth.clients");
        $data["modalType"] = "";
        $data['stats'] = [
            'total' => objModel::count(),
            'active' => objModel::where('is_blocked', 0)->count(),
            'blocked' => objModel::where('is_blocked', 1)->count(),
            'new_this_month' => objModel::whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count(),
            'with_orders' => objModel::has('orders')->count(),
        ];
        $data['nationalities'] = Country::active()->ordered()->get(['id', 'name_ar', 'name_en']);
        $data['cities'] = City::active()->ordered()->get(['id', 'name_ar', 'name_en']);
        return view($this->folderPath . '.index', $data);
    }

    public function bulkUpdateStatus(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_TOGGLE_STATUS)) {
            return response()->json([
                'message' => 'ليس لديك صلاحية لتغيير حالة العملاء'
            ], 403);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'status' => ['required', 'in:active,blocked'],
        ]);

        $isBlocked = $validated['status'] === 'blocked' ? 1 : 0;
        $updated = objModel::whereIn('id', $validated['ids'])->update(['is_blocked' => $isBlocked]);

        return response()->json([
            'message' => 'تم تحديث حالة ' . $updated . ' عميل بنجاح.',
            'updated' => $updated,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE)) {
            abort(403, 'ليس لديك صلاحية لإضافة عميل جديد');
        }

        $countries = \App\Models\Country::active()->ordered()->get();
        $client_categories = ClientCategory::all();
        $cities = \App\Models\City::active()->ordered()->get();
        return view($this->folderPath . '.create', compact('countries', 'cities', 'client_categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, objAction $action)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE)) {
            return response()->json([
                'code' => 403,
                'message' => 'ليس لديك صلاحية لإضافة عميل جديد'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:users,email',
            'client_category_id' => 'nullable|exists:fn_client_categories,id',
            'nationality_id' => 'nullable|exists:countries,id',
            'national_num' => 'nullable|string|max:50',
            'birthdate' => 'nullable|date',
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'rooms_num' => 'nullable|integer|min:0',
            'family_num' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'job' => 'nullable|string|max:100'
        ], [
            // رسائل الفاليديشن
        ]);

        $normalizedPhone = $this->normalizeSaudiPhone($data['phone']);

        if (!$normalizedPhone) {
            throw ValidationException::withMessages([
                'phone' => 'رقم الجوال غير صحيح. الصيغة المتاحة: 05XXXXXXXX أو 5XXXXXXXX أو +9665XXXXXXXX.',
            ]);
        }

        $phoneCandidates = array_values(array_unique(array_filter([
            $data['phone'],
            $normalizedPhone,
            '0' . $normalizedPhone,
            '966' . $normalizedPhone,
            '+966' . $normalizedPhone,
        ])));

        if (objModel::whereIn('phone', $phoneCandidates)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'رقم الجوال مستخدم مسبقاً.',
            ]);
        }

        $data['phone'] = $normalizedPhone;
        $data['phone_code'] = '+966';
        $data['password'] = bcrypt('123456');
        $data['register_from'] = 'dashboard';
        $data['is_blocked'] = 0;

        return DB::transaction(function () use ($data) {

            $parentAccount = Account::query()->where('type', '=', AccountTypesEnum::Clients->value)
                ->lockForUpdate()
                ->first();

            if (!$parentAccount) {
                return response()->json([
                    'code' => 422,
                    'message' => 'لا يوجد حساب رئيسي للعملاء في جدول الحسابات. يرجى إنشاء حساب من نوع Clients أولاً.'
                ], 422);
            }

            $lastCode = Account::where('parent_id', $parentAccount->id)->max('code');
            if (!$lastCode) {
                $lastCode = (int)($parentAccount->code . '00000');
            }

            $newAccount = Account::create([
                'name' => $data['name'],
                'operation_type' => $parentAccount->operation_type,
                'status' => $parentAccount->status,
                'level' => $parentAccount->level + 1,
                'type' => AccountTypesEnum::Client->value,
                'parent_id' => $parentAccount->id,
                'code' => $lastCode + 1,
                'created_by_id' => Auth::id(),
            ]);

            $data['account_id'] = $newAccount->id;
            $financeClientCategoryId = $this->resolveFinanceClientCategoryId($data['client_category_id'] ?? null);

            Client::create([
                'is_company' => 0,
                'client_category_id' => $financeClientCategoryId,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? '',
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? null,
                'tax_number' => $data['national_num'] ?? null,
                'code' => $newAccount->code,
                'days_credit_period' => 30,
                'credit_limit' => 30,
                'created_by_id' => Auth::id(),
                'account_id' => $newAccount->id,
            ]);
            objModel::create($data);
            return response()->json([
                'code' => 200,
                'message' => __('ingaz.client_created_successfully'),
                'url' => route($this->mainRoute . '.index')
            ]);
        });
    }

    private function normalizeSaudiPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '05')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
    }

    private function resolveFinanceClientCategoryId($selectedCategoryId = null): int
    {
        if ($selectedCategoryId) {
            return (int) $selectedCategoryId;
        }

        $existingCategoryId = ClientCategory::query()->value('id');

        if ($existingCategoryId) {
            return (int) $existingCategoryId;
        }

        $categoryData = ['name' => 'عام'];

        if (\Illuminate\Support\Facades\Schema::hasColumn('fn_client_categories', 'created_by_id') && Auth::id()) {
            $categoryData['created_by_id'] = Auth::id();
        }

        return (int) ClientCategory::query()->create($categoryData)->id;
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW)) {
            abort(403, 'ليس لديك صلاحية لعرض تفاصيل العميل');
        }

        $client = objModel::with([
            'nationality',
            'addresses.country',
            'orders' => function ($query) {
                $query->with([
                    'maid',
                    'details.admin',
                    'package',
                    'address.country'
                ])->latest();
            },
            'complaints' => function ($query) {
                $query->with(['assignedTo'])->latest();
            },
            'messages' => function ($query) {
                $query->latest();
            },
            'rentalContracts' => function ($query) {
                $query->with(['maid', 'info'])->latest();
            },
            'clientAttachments.uploader',
        ])->findOrFail($id);

        $activities = $client->activities()->latest()->limit(15)->get()->map(function ($activity) {
            $activity->causer_name = null;
            $causerType = $activity->causer_type;

            if ($causerType && class_exists($causerType)) {
                $activity->causer_name = optional($activity->causer)->name;
            }

            return $activity;
        });

        // جلب طلبات نقل الكفالة للعميل
        $sponsorshipTransfers = \App\Models\SponsorshipTransfer::with([
            'contract',
            'oldSponsor',
            'newSponsor',
            'approvedBy',
            'transferredBy',
            'failedBy'
        ])->where(function ($query) use ($client) {
            $query->where('old_sponsor_id', $client->id)
                ->orWhere('new_sponsor_id', $client->id);
        })->orderBy('created_at', 'desc')->get();

        $invoices = Invoice::with(['contract'])
            ->whereHas('contract', function ($query) use ($client) {
                $query->where('user_id', $client->id);
            })
            ->latest()
            ->get();

        $stats = [
            'total_orders' => $client->orders->count(),
            'active_orders' => $client->orders->whereNotIn('status', ['completed', 'done', 'refused', 'return'])->count(),
            'completed_orders' => $client->orders->whereIn('status', ['completed', 'done'])->count(),
            'rental_contracts' => $client->rentalContracts->count(),
            'transfer_requests' => $sponsorshipTransfers->count(),
            'complaints' => $client->complaints->count(),
        ];

        $lastInteractions = collect([
            optional($client->orders->first())->created_at,
            optional($client->messages->first())->created_at,
            optional($client->complaints->first())->created_at,
            optional($sponsorshipTransfers->first())->created_at,
        ])->filter()->sortDesc()->values();

        $lastInteractionAt = $lastInteractions->first();

        return view($this->folderPath . '.show', compact(
            'client',
            'sponsorshipTransfers',
            'invoices',
            'stats',
            'activities',
            'lastInteractionAt'
        ));
    }

    public function storeAttachments(Request $request, $id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
            abort(403);
        }

        $client = objModel::findOrFail($id);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['required', 'file', 'max:20480'],
        ], [
            'attachments.required' => 'يرجى اختيار ملف واحد على الأقل.',
            'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز 20 ميجابايت.',
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid()->toString() . ($extension ? '.' . $extension : '');
            $path = $file->storeAs('clients/attachments/' . $client->id, $fileName, 'public');

            ClientAttachment::create([
                'user_id' => $client->id,
                'uploaded_by' => auth('admin')->id() ?: auth()->id(),
                'title' => $validated['title'] ?? null,
                'original_name' => $originalName,
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }

        return redirect()
            ->route('clients.show', $client->id)
            ->with('success', 'تم رفع مرفقات العميل بنجاح.');
    }

    public function downloadAttachments(Request $request, $id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW) && !@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW)) {
            abort(403);
        }

        $client = objModel::with('clientAttachments')->findOrFail($id);
        $selectedIds = collect((array) $request->input('attachments', []))
            ->filter(fn($value) => is_numeric($value))
            ->map(fn($value) => (int) $value)
            ->values();

        $attachments = $client->clientAttachments;
        if ($selectedIds->isNotEmpty()) {
            $attachments = $attachments->whereIn('id', $selectedIds);
        }

        $attachments = $attachments->filter(fn(ClientAttachment $attachment) => $attachment->existsOnDisk())->values();

        if ($attachments->isEmpty()) {
            return redirect()->back()->with('error', 'لا توجد مرفقات صالحة للتنزيل.');
        }

        $zip = new \ZipArchive();
        $zipFile = tempnam(sys_get_temp_dir(), 'client-attachments-');

        if ($zip->open($zipFile, \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'تعذر تجهيز ملف المرفقات.');
        }

        foreach ($attachments as $attachment) {
            $zip->addFile(
                Storage::disk('public')->path($attachment->path),
                $this->buildAttachmentDownloadName($attachment)
            );
        }

        $zip->close();

        if (filesize($zipFile) <= 0) {
            @unlink($zipFile);
            return redirect()->back()->with('error', 'لا توجد مرفقات صالحة للتنزيل.');
        }

        return response()
            ->download($zipFile, 'client-' . $client->id . '-attachments.zip')
            ->deleteFileAfterSend(true);
    }

    public function downloadAttachment($id, ClientAttachment $attachment)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW) && !@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW)) {
            abort(403);
        }

        objModel::findOrFail($id);

        if ((int) $attachment->user_id !== (int) $id || !$attachment->existsOnDisk()) {
            abort(404);
        }

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }

    public function viewAttachment($id, ClientAttachment $attachment)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW) && !@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW)) {
            abort(403);
        }

        objModel::findOrFail($id);

        if ((int) $attachment->user_id !== (int) $id || !$attachment->existsOnDisk()) {
            abort(404);
        }

        $filename = str_replace(['"', "\r", "\n"], '', $attachment->original_name ?: 'attachment');

        return response()->file(Storage::disk('public')->path($attachment->path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function deleteAttachment($id, ClientAttachment $attachment)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
            abort(403);
        }

        objModel::findOrFail($id);

        if ((int) $attachment->user_id !== (int) $id) {
            abort(404);
        }

        if ($attachment->path) {
            Storage::disk('public')->delete($attachment->path);
        }

        $attachment->delete();

        return redirect()
            ->route('clients.show', $id)
            ->with('success', 'تم حذف مرفق العميل بنجاح.');
    }

    private function buildAttachmentDownloadName(ClientAttachment $attachment): string
    {
        $extension = pathinfo($attachment->original_name ?: $attachment->path, PATHINFO_EXTENSION);
        $baseName = pathinfo($attachment->original_name ?: 'attachment', PATHINFO_FILENAME);
        $safeName = Str::slug($baseName, '-');

        if ($safeName === '') {
            $safeName = 'attachment-' . $attachment->id;
        }

        return $attachment->id . '-' . $safeName . ($extension ? '.' . $extension : '');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
            abort(403, 'ليس لديك صلاحية لتعديل العميل');
        }

        $client = objModel::findOrFail($id);
        $countries = \App\Models\Country::active()->ordered()->get();
        $client_categories = ClientCategory::all();
        $cities = \App\Models\City::active()->ordered()->get();

        return view($this->folderPath . '.edit', compact('client', 'countries', 'cities', 'client_categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل العميل'
            ], 403);
        }

        $client = objModel::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255|unique:users,email,' . $client->id,
            'nationality_id' => 'nullable|exists:countries,id',
            'client_category_id' => 'nullable|exists:fn_client_categories,id',
            'national_num' => 'nullable|string|max:50',
            'birthdate' => 'nullable|date',
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'rooms_num' => 'nullable|integer|min:0',
            'family_num' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'job' => 'nullable|string|max:100',
            'is_blocked' => 'required|boolean'
        ], [
            'name.required' => __('ingaz.client_name_required'),
            'name.string' => __('ingaz.client_name_required'),
            'name.max' => __('ingaz.client_name_max'),
            'phone.required' => __('ingaz.client_phone_required'),
            'phone.string' => __('ingaz.client_phone_required'),
            'phone.max' => __('ingaz.client_phone_max'),
            'phone.unique' => __('ingaz.client_phone_already_exists'),
            'email.email' => __('ingaz.client_email_invalid'),
            'email.max' => __('ingaz.client_email_max'),
            'email.unique' => __('ingaz.client_email_already_exists'),
            'nationality_id.exists' => __('ingaz.client_nationality_not_found'),
            'national_num.string' => __('ingaz.client_national_num_max'),
            'national_num.max' => __('ingaz.client_national_num_max'),
            'birthdate.date' => __('ingaz.client_birthdate_invalid'),
            'phone2.string' => __('ingaz.client_phone2_max'),
            'phone2.max' => __('ingaz.client_phone2_max'),
            'address.string' => __('ingaz.client_address_max'),
            'address.max' => __('ingaz.client_address_max'),
            'city.string' => __('ingaz.client_city_max'),
            'city.max' => __('ingaz.client_city_max'),
            'rooms_num.integer' => __('ingaz.client_rooms_num_integer'),
            'rooms_num.min' => __('ingaz.client_rooms_num_min'),
            'family_num.integer' => __('ingaz.client_family_num_integer'),
            'family_num.min' => __('ingaz.client_family_num_min'),
            'children.integer' => __('ingaz.client_children_integer'),
            'children.min' => __('ingaz.client_children_min'),
            'job.string' => __('ingaz.client_job_max'),
            'job.max' => __('ingaz.client_job_max'),
            'is_blocked.required' => __('ingaz.client_is_blocked_required'),
            'is_blocked.boolean' => __('ingaz.client_is_blocked_boolean')
        ]);

        $normalizedPhone = $this->normalizeSaudiPhone($data['phone']);

        if (!$normalizedPhone) {
            throw ValidationException::withMessages([
                'phone' => 'رقم الجوال غير صحيح. الصيغة المتاحة: 05XXXXXXXX أو 5XXXXXXXX أو +9665XXXXXXXX.',
            ]);
        }

        $phoneCandidates = array_values(array_unique(array_filter([
            $data['phone'],
            $normalizedPhone,
            '0' . $normalizedPhone,
            '966' . $normalizedPhone,
            '+966' . $normalizedPhone,
        ])));

        if (objModel::where('id', '!=', $client->id)->whereIn('phone', $phoneCandidates)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'رقم الجوال مستخدم مسبقاً.',
            ]);
        }

        $data['phone'] = $normalizedPhone;
        $data['phone_code'] = '+966';

        $client->update($data);
        $clientAccount = Account::find($client->account_id);
        $clientFinance = Client::where('account_id', $client->account_id)->first();
        if ($clientFinance) {
            $clientFinance->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? '',
                'address' => $data['address'] ?? '',
                'city' => $data['city'] ?? null,
                'tax_number' => $data['national_num'] ?? null,
                'client_category_id' => $data['client_category_id'] ?? $clientFinance->client_category_id,
            ]);
        }
        if ($clientAccount) {
            $clientAccount->update(['name' => $data['name']]);
        }
        return jsonSuccess(null, null, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_DELETE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف العميل'
            ], 403);
        }

        $action->delete($id);
        return jsonSuccess();
    }

    public function exportPdf()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_PDF)) {
            abort(403, 'ليس لديك صلاحية لتصدير بيانات العملاء بصيغة PDF');
        }

        $clients = objModel::all();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة العملاء</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    direction: rtl;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .header h1 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .print-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .print-button:hover {
                    background-color: #45a049;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .status-active {
                    color: green;
                }
                .status-blocked {
                    color: red;
                }
                @media print {
                    .print-button {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                }
            </style>
            <script>
                function printDocument() {
                    window.print();
                }
                window.onload = function() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="header">
                <h1>قائمة العملاء</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العميل</th>
                        <th>الاسم</th>
                        <th>رقم الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجنسية</th>
                        <th>رقم الهوية</th>
                        <th>تاريخ الميلاد</th>
                        <th>العنوان</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($clients as $client) {
            $statusClass = $client->is_blocked ? 'status-blocked' : 'status-active';
            $statusText = $client->is_blocked ? 'محظور' : 'مفعل';

            $html .= '<tr>
                <td>' . $client->id . '</td>
                <td>' . $client->name . '</td>
                <td>' . $client->phone_code . $client->phone . '</td>
                <td>' . $client->email . '</td>
                <td>' . ($client->nationality->title ?? '') . '</td>
                <td>' . $client->national_num . '</td>
                <td>' . $client->birthdate . '</td>
                <td>' . $client->address . '</td>
                <td>' . $client->city . '</td>
                <td class="' . $statusClass . '">' . $statusText . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="clients.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportPdfNewWindow()
    {
        $clients = objModel::all();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة العملاء</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    direction: rtl;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .header h1 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .print-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .print-button:hover {
                    background-color: #45a049;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .status-active {
                    color: green;
                }
                .status-blocked {
                    color: red;
                }
                @media print {
                    .print-button {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                }
            </style>
            <script>
                function printDocument() {
                    window.print();
                }
                window.onload = function() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="header">
                <h1>قائمة العملاء</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العميل</th>
                        <th>الاسم</th>
                        <th>رقم الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجنسية</th>
                        <th>رقم الهوية</th>
                        <th>تاريخ الميلاد</th>
                        <th>العنوان</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($clients as $client) {
            $statusClass = $client->is_blocked ? 'status-blocked' : 'status-active';
            $statusText = $client->is_blocked ? 'محظور' : 'مفعل';

            $html .= '<tr>
                <td>' . $client->id . '</td>
                <td>' . $client->name . '</td>
                <td>' . $client->phone_code . $client->phone . '</td>
                <td>' . $client->email . '</td>
                <td>' . ($client->nationality->title ?? '') . '</td>
                <td>' . $client->national_num . '</td>
                <td>' . $client->birthdate . '</td>
                <td>' . $client->address . '</td>
                <td>' . $client->city . '</td>
                <td class="' . $statusClass . '">' . $statusText . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="clients.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_EXCEL)) {
            abort(403, 'ليس لديك صلاحية لتصدير بيانات العملاء بصيغة Excel');
        }

        $clients = objModel::all();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم العميل',
            'الاسم',
            'رقم الهاتف',
            'البريد الإلكتروني',
            'الجنسية',
            'رقم الهوية',
            'تاريخ الميلاد',
            'العنوان',
            'المدينة',
            'الحالة'
        ];

        // Set headers in first row
        $sheet->fromArray($headers, NULL, 'A1');

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F2F2F2',
                ],
            ],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($clients as $client) {
            $sheet->setCellValue('A' . $row, $client->id);
            $sheet->setCellValue('B' . $row, $client->name);
            $sheet->setCellValue('C' . $row, $client->phone_code . $client->phone);
            $sheet->setCellValue('D' . $row, $client->email);
            $sheet->setCellValue('E' . $row, $client->nationality->title ?? '');
            $sheet->setCellValue('F' . $row, $client->national_num);
            $sheet->setCellValue('G' . $row, $client->birthdate);
            $sheet->setCellValue('H' . $row, $client->address);
            $sheet->setCellValue('I' . $row, $client->city);
            $sheet->setCellValue('J' . $row, $client->is_blocked ? 'محظور' : 'مفعل');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="clients.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'clients.xlsx', $headers)->deleteFileAfterSend(true);
    }
}
