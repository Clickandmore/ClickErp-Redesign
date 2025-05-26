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

class AdminTravelHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $data = TravelHistory::orderBy('id', 'desc');
        if ($search) {
            $data = $data->where(function ($query) use ($search) {
                $query->where('travel_type', 'like', '%' . $search . '%')
                    ->orWhere('travel_with', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('arrival_airport', 'like', '%' . $search . '%')
                    ->orWhere('arrival_date', 'like', '%' . $search . '%')
                    ->orWhere('arrival_time', 'like', '%' . $search . '%')
                    ->orWhere('flight_company', 'like', '%' . $search . '%')
                    ->orWhere('flight_number', 'like', '%' . $search . '%');
            });
        }
        $data = $data->paginate(15)->appends(['search' => $search]);
        return view('admin.travel_history.index', compact('data', 'search'));
    }

    public function create(Request $request, $id = null)
    {
        $userId = $request->get('userId');
        $maidId = $request->get('maidId');
        $orderId = $request->get('orderId');
        $data = null;
        $order = null;

        if ($id != null) {
            $data = TravelHistory::with([
                'user',
                'maid',
                'order.user',
                'order.maid'
            ])->find($id);

            if ($data) {
                $userId = $data->user_id;
                $maidId = $data->maid_id;
                $orderId = $data->order_id;
                $order = $data->order;
            }
        }

        $clientsList = $this->usersList();
        $maidsList = $this->maidsList();

        // Get all contracts with status 'tazker' and their relationships
        $contracts = Contract::where('status', 'tazker')
            ->with(['user', 'maid', 'info'])
            ->get();

        // If we have an order from the travel record or from the request, get it
        if ($orderId) {
            $selectedOrder = Contract::with(['user', 'maid', 'info'])
                ->where('id', $orderId)
                ->first();

            if ($selectedOrder && !$contracts->contains('id', $selectedOrder->id)) {
                $contracts->push($selectedOrder);
            }
        }

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

        if ($id != null) {
            $data = TravelHistory::find($id);
        }

        $data = TravelHistory::updateOrCreate(
            [
                'id' => $id,
            ],
            [
                'user_id' => $request->user_id,
                'maid_id' => $request->maid_id,
                'order_id' => $request->order_id,
                'travel_type' => $request->travel_type,
                'travel_with' => $request->travel_with,
                'status' => $request->status ?? 'pending',
                'notes' => $request->notes,
                //** */
                'arrival_airport' => $request->arrival_airport,
                'arrival_date' => $request->arrival_date,
                'arrival_time' => $request->arrival_time,
                'flight_company' => $request->flight_company,
                'flight_number' => $request->flight_number,
            ]
        );

        //** FILES */
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
        $file = TravelHistoryFiles::find($id);
        if (!$file) {
            return response()->json(['message' => __('admin.file_not_found')], 404);
        }
        $filePath = Constants::PUBLIC_TRAVEL_DIR . '/' . $file->file;
        if (File::exists($filePath)) {
            File::delete($filePath);
            $file->delete();
            $remainingFiles = TravelHistoryFiles::where('travel_id', $file->travel_id)->exists();
            if (!$remainingFiles) {
                return response()->json(['message' => __('admin.file_deleted'), 'no_files' => true]);
            }
            return response()->json(['message' => __('admin.file_deleted')]);
        }
        return response()->json(['message' => __('admin.file_not_found')], 404);
    }

}
