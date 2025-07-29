<?php

namespace App\Http\Controllers\admin;

use App\Constants;
use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ingaz\Order;
use App\Models\TravelHistory;
use App\Models\TravelHistoryFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use DataTables;

class AdminTravelHistoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TravelHistory::with(['user', 'maid', 'maid.nationality', 'maid.nationality.translations'])
                ->where('travel_type', '!=', 'deportation') // استبعاد رحلات الترحيل
                ->orderBy('id', 'desc');

            // Apply filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('travel_type', 'like', '%' . $search . '%')
                        ->orWhere('travel_with', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhere('arrival_airport', 'like', '%' . $search . '%')
                        ->orWhere('arrival_date', 'like', '%' . $search . '%')
                        ->orWhere('arrival_time', 'like', '%' . $search . '%')
                        ->orWhere('flight_company', 'like', '%' . $search . '%')
                        ->orWhere('flight_number', 'like', '%' . $search . '%')
                        ->orWhere('order_id', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('maid', function($q) use ($search) {
                            $q->where('passport_num', 'like', '%' . $search . '%')
                                ->orWhere('name', 'like', '%' . $search . '%');
                        });
                });
            }

            if ($request->filled('travel_type')) {
                $query->where('travel_type', $request->travel_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_data', function($row) {
                    $html = '<div class="user-info">';
                    if ($row->user) {
                        $html .= '<span class="fw-bold">' . $row->user->name . '</span><br>';
                        $html .= '<small class="text-muted">رقم: ' . $row->user->id . '</small><br>';
                        $html .= '<small class="text-muted">' . ($row->user->mobile ?? 'لا يوجد هاتف') . '</small>';
                    } else {
                        $html .= '<span class="text-muted">غير محدد</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('maid_data', function($row) {
                    $html = '<div class="maid-info">';
                    if ($row->maid) {
                        $html .= '<span class="fw-bold">' . $row->maid->name . '</span><br>';
                        $html .= '<small class="text-muted">الجواز: ' . ($row->maid->passport_num ?? 'غير محدد') . '</small>';

                        // الجنسية
                        if ($row->maid->nationality) {
                            $nationality = 'غير محدد';
                            if ($row->maid->nationality->translations && $row->maid->nationality->translations->count() > 0) {
                                $translation = $row->maid->nationality->translations->where('locale', app()->getLocale())->first();
                                if ($translation) {
                                    $nationality = $translation->sub_title;
                                }
                            }
                            $html .= '<br><small class="text-muted">الجنسية: ' . $nationality . '</small>';
                        }
                    } else {
                        $html .= '<span class="text-muted">غير محدد</span>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('travel_details', function($row) {
                    $html = '<div class="travel-details">';

                    // المطار
                    $airports = [
                        'RUH' => 'الرياض',
                        'JED' => 'جدة',
                        'DMM' => 'الدمام',
                        'MED' => 'المدينة المنورة',
                        'TUU' => 'تبوك',
                        'AHB' => 'أبها',
                        'TIF' => 'الطائف',
                        'ELQ' => 'القصيم',
                        'URY' => 'الجوف',
                        'HAS' => 'حائل',
                        'BHH' => 'الباحة',
                        'GIZ' => 'جازان',
                    ];

                    $airport = $airports[$row->arrival_airport] ?? $row->arrival_airport;
                    $html .= '<i class="fa fa-plane"></i> ' . $airport . '<br>';

                    // التاريخ
                    if ($row->arrival_date) {
                        $html .= '<i class="fa fa-calendar"></i> ' . \Carbon\Carbon::parse($row->arrival_date)->format('Y-m-d') . '<br>';
                    }

                    // الوقت
                    if ($row->arrival_time) {
                        $html .= '<i class="fa fa-clock"></i> ' . $row->arrival_time;
                    }

                    $html .= '</div>';
                    return $html;
                })
                ->addColumn('actions', function($row) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // تعديل
                    $actions .= '<li><a class="dropdown-item" href="' . route('travel.control') . '?id=' . $row->id . '&userId=' . $row->user_id . '&maidId=' . $row->maid_id . '&orderId=' . $row->order_id . '">
                                    <i class="fas fa-edit"></i> ' . __('admin.edit') . '
                                </a></li>';

                    if (auth('admin')->user() && \AdminGuardHasPerm(\App\PermKeys::TRAVEL_CONTROL)) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';

                        // حذف
                        $actions .= '<li><a class="dropdown-item" href="javascript:void(0)" onclick="deleteTravel(' . $row->id . ')">
                                        <i class="fas fa-trash"></i> ' . __('admin.delete') . '
                                    </a></li>';
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['user_data', 'maid_data', 'travel_details', 'actions'])
                ->make(true);
        }

        // For non-AJAX requests (initial page load)
        return view('admin.travel_history.index');
    }

    public function create(Request $request, $id = null)
    {
        $userId = $request->get('userId');
        $maidId = $request->get('maidId');
        $orderId = $request->get('orderId');
        $contractId = $request->get('contract_id');
        $data = null;
        $order = null;

        // First check if there's an existing travel record for this order
        if ($orderId) {
            $data = TravelHistory::where('order_id', $orderId)
                ->with(['user', 'maid', 'order.user', 'order.maid'])
                ->first();
        }

        // If no data found by order_id, check by id parameter
        if (!$data && $id) {
            $data = TravelHistory::with([
                'user',
                'maid',
                'order.user',
                'order.maid'
            ])->find($id);
        }

        // If we found data, use its values
        if ($data) {
            $userId = $data->user_id;
            $maidId = $data->maid_id;
            $orderId = $data->order_id;
            $order = $data->order;
        }

        $clientsList = $this->usersList();
        $maidsList = $this->maidsList();

        // Get all contracts with status 'tazker' and their relationships
        $contracts = Contract::where('status', 'tazker')
            ->with(['user', 'maid', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])
            ->get();

        // If we have an order from the travel record or from the request, get it
        if ($orderId) {
            $selectedOrder = Contract::with(['user', 'maid', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])
                ->where('id', $orderId)
                ->first();

            if ($selectedOrder && !$contracts->contains('id', $selectedOrder->id)) {
                $contracts->push($selectedOrder);
            }
        }

        // If we have a contract_id from the request, use it to get the contract with maid info
        if ($contractId) {
            $selectedContract = Contract::with(['user', 'maid', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])
                ->where('id', $contractId)
                ->first();

            if ($selectedContract) {
                $orderId = $contractId; // Use contract_id as order_id
                $userId = $selectedContract->user_id;
                $maidId = $selectedContract->maid_id;
                $order = $selectedContract; // Set the contract as order for display

                if (!$contracts->contains('id', $selectedContract->id)) {
                    $contracts->push($selectedContract);
                }
            }
        }

        // Debug: Log contracts data to see if user data is loaded
        \Log::info('Contracts data for travel form:', [
            'contracts_count' => $contracts->count(),
            'contracts_with_user' => $contracts->whereNotNull('user')->count(),
            'sample_contract' => $contracts->first() ? [
                'id' => $contracts->first()->id,
                'user_id' => $contracts->first()->user_id,
                'user_name' => $contracts->first()->user->name ?? 'null',
                'user_city' => $contracts->first()->user->city ?? 'null',
            ] : 'no contracts'
        ]);

        $ordersList = [];
        return view('admin.travel_history.control', compact('data', 'clientsList', 'maidsList', 'ordersList', 'contracts', 'id', 'userId', 'maidId', 'order', 'orderId'));
    }

    public function store(Request $request, $id = null)
    {
        // Validation
        $request->validate([
            'user_id' => 'required',
            'maid_id' => 'required',
            'travel_type' => 'required',
            'travel_with' => 'required',
        ]);

        // First check if there's an existing travel record for this order
        if ($request->order_id || $request->contract_id) {
            $orderId = $request->order_id ?: $request->contract_id;
            $existingTravel = TravelHistory::where('order_id', $orderId)->first();

            if ($existingTravel) {
                // Update existing record
                $data = $existingTravel;
                $data->update([
                    'user_id' => $request->user_id,
                    'maid_id' => $request->maid_id,
                    'travel_type' => $request->travel_type,
                    'travel_with' => $request->travel_with,
                    'status' => $request->status ?? 'pending',
                    'notes' => $request->notes,
                    'arrival_airport' => $request->arrival_airport,
                    'arrival_date' => $request->arrival_date,
                    'arrival_time' => $request->arrival_time,
                    'flight_company' => $request->flight_company,
                    'flight_number' => $request->flight_number,
                ]);
            } else {
                // Create new record only if no existing record found
                $data = TravelHistory::create([
                    'user_id' => $request->user_id,
                    'maid_id' => $request->maid_id,
                    'order_id' => $orderId,
                    'travel_type' => $request->travel_type,
                    'travel_with' => $request->travel_with,
                    'status' => $request->status ?? 'pending',
                    'notes' => $request->notes,
                    'arrival_airport' => $request->arrival_airport,
                    'arrival_date' => $request->arrival_date,
                    'arrival_time' => $request->arrival_time,
                    'flight_company' => $request->flight_company,
                    'flight_number' => $request->flight_number,
                ]);
            }
        } else {
            // If no order_id provided, create new record
            $data = TravelHistory::create([
                'user_id' => $request->user_id,
                'maid_id' => $request->maid_id,
                'order_id' => $request->order_id,
                'travel_type' => $request->travel_type,
                'travel_with' => $request->travel_with,
                'status' => $request->status ?? 'pending',
                'notes' => $request->notes,
                'arrival_airport' => $request->arrival_airport,
                'arrival_date' => $request->arrival_date,
                'arrival_time' => $request->arrival_time,
                'flight_company' => $request->flight_company,
                'flight_number' => $request->flight_number,
            ]);
        }

        //** FILES */
        if (!empty($request->file('files'))) {
            foreach ($request->file('files') as $k => $file) {
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = $originalFileName . '-' . time() . $k . '.' . $file->extension();
                $file->move(Constants::PUBLIC_TRAVEL_DIR, $fileName);
                TravelHistoryFiles::create([
                    'travel_id' => $data->id,
                    'file' => $fileName,
                ]);
            }
        }

        // تحديث تاريخ الوصول في contract_info إذا كان هناك عقد مرتبط
        if ($request->arrival_date && ($request->order_id || $request->contract_id)) {
            $orderId = $request->order_id ?: $request->contract_id;

            // البحث عن العقد المرتبط
            $contract = Contract::where('id', $orderId)->first();

            if ($contract) {
                // تحديث أو إنشاء contract_info
                if ($contract->info) {
                    // تحديث contract_info الموجود
                    $contract->info->update([
                        'arrival_date' => $request->arrival_date
                    ]);
                } else {
                    // إنشاء contract_info جديد
                    $contract->info()->create([
                        'arrival_date' => $request->arrival_date
                    ]);
                }

                \Log::info('Updated contract_info arrival_date', [
                    'contract_id' => $contract->id,
                    'arrival_date' => $request->arrival_date,
                    'travel_history_id' => $data->id
                ]);
            }
        }

        return redirect(route('travel'))->with('success', __('admin.success'));
    }

    public function edit(Request $request, $id)
    {
        return $this->create($request, $id);
    }

    public function update(Request $request, $id)
    {
        return $this->store($request, $id);
    }

    public function destroy(string $id)
    {
        $data = TravelHistory::find($id);
        // get order by order_id
        $order = Order::find($data->order_id);
        if ($order) {
            $order->status = ContractStatusEnum::Taffez;
            $order->save();
        }
        $data ? $data->delete() : '';
        return redirect()->back()->with('success', __('admin.success'));
    }

    public function storeTravelHistory(Request $request)
    {
        // Validation
        $request->validate([
            'travel_type' => 'required',
            'travel_with' => 'required',
            'files.*' => 'file|mimes:jpeg,png,jpg,pdf|max:4096', // Adjust mime types and max size as needed
        ]);

        $order = Order::find($request->order_id);

        // Create a new TravelHistory record
        $data = TravelHistory::create([
            'user_id' => $order->user_id ?? 0,
            'maid_id' => $order->maid_id ?? 0,
            'order_id' => $request->order_id,
            'travel_type' => $request->travel_type,
            'travel_with' => $request->travel_with,
            'status' => 'pending',
            'notes' => $request->notes,
            //** */
            'arrival_airport' => $request->arrival_airport,
            'arrival_date' => $request->arrival_date,
            'arrival_time' => $request->arrival_time,
            'flight_company' => $request->flight_company,
            'flight_number' => $request->flight_number,
        ]);

        // Handle file uploads
        if (!empty($request->file('files'))) {
            foreach ($request->file('files') as $k => $file) {
                $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME); // Get the original filename without extension
                $fileName = $originalFileName . '-' . time() . $k . '.' . $file->extension();
                $file->move(Constants::PUBLIC_TRAVEL_DIR, $fileName);
                TravelHistoryFiles::create([
                    'travel_id' => $data->id,
                    'file' => $fileName,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function downloadFile($id)
    {
        $file = TravelHistoryFiles::find($id);

        if (!$file) {
            return redirect()->back()->with('error', __('admin.file_not_found'));
        }

        $filePath = Constants::PUBLIC_TRAVEL_DIR . '/' . $file->file;

        if (File::exists($filePath)) {
            return Response::download($filePath, $file->file);
        }

        return redirect()->back()->with('error', __('admin.file_not_found'));
    }

    public function deleteFile($id)
    {
        try {
            $file = TravelHistoryFiles::findOrFail($id);
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملف بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الملف'
            ], 500);
        }
    }

    public function checkTripExists(Request $request)
    {
        try {
            $orderId = $request->get('order_id');

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف العقد مطلوب'
                ], 400);
            }

            $exists = TravelHistory::where('order_id', $orderId)->exists();

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'message' => $exists ? 'يوجد رحلة لهذا العقد' : 'لا يوجد رحلة لهذا العقد'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق من وجود الرحلة'
            ], 500);
        }
    }
}

