<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Models\ShelterWorkers;
use App\Models\ingaz\Nationality;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ShelterWorkersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:shelter_workers_view|shelter_workers_control,admin', ['only' => ['index', 'show', 'download']]);
        $this->middleware('permission:shelter_workers_create|shelter_workers_control,admin', ['only' => ['create', 'store']]);
        $this->middleware('permission:shelter_workers_edit|shelter_workers_control,admin', ['only' => ['edit', 'update']]);
        $this->middleware('permission:shelter_workers_delete|shelter_workers_control,admin', ['only' => ['destroy', 'deleteFile']]);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $worker_type = $request->input('wt');
        $data = ShelterWorkers::where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('city', 'like', '%' . $search . '%')
                ->orWhere('worker_name', 'like', '%' . $search . '%')
                ->orWhere('residence_num', 'like', '%' . $search . '%')
                ->orWhere('passport_num', 'like', '%' . $search . '%')
            ;
        });
        if ($status && $status != 'all') {
            $data = $data->where('shelter_status', $status);
        }
        if ($worker_type && $worker_type != 'all') {
            $data = $data->where('worker_type', $worker_type);
        }
        $data = $data->paginate(15)->appends(['search' => $search]);
        return view('admin.shelter_workers.index', compact('data', 'search', 'status', 'worker_type'));
    }

    public function show($id)
    {
        $shelterWorker = ShelterWorkers::find($id);
        return view('admin.shelter_workers.show', compact('shelterWorker'));
    }

    public function create($id = null)
    {
        $data = null;
        if ($id != null) {
            $data = ShelterWorkers::find($id);
        }
        $nationalitiesList = Nationality::all();
        return view('admin.shelter_workers.control', compact('data', 'nationalitiesList'));
    }

    public function store(Request $request, $id = null)
    {
        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'worker_name' => 'required|string|max:255',
            'residence_num' => 'required|string|max:255',
            'passport_num' => 'required|string|max:255',
            'nationality_id' => 'required|integer',
            'shelter_status' => 'required|string|max:255',
            'received_by' => 'required|string|max:255',
            'received_by_other' => 'required_if:received_by,other',
            'status' => 'required|string|max:255',
            // Add validation for new fields
            'travel_doc' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'settlement' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'graduation' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'reserve' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'reserve_date' => 'nullable|date',
            'travel' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'travel_doc_notes' => 'nullable|string',
            'settlement_notes' => 'nullable|string',
            'graduation_notes' => 'nullable|string',
            'reserve_notes' => 'nullable|string',
            'travel_notes' => 'nullable|string',
        ]);

        // Find existing record or create a new one
        $data = ShelterWorkers::updateOrCreate(
            [
                'id' => $id,
            ],
            [
                'name' => $request->name,
                'city' => $request->city,
                'worker_name' => $request->worker_name,
                'residence_num' => $request->residence_num,
                'passport_num' => $request->passport_num,
                'nationality_id' => $request->nationality_id,
                'shelter_status' => $request->shelter_status,
                'received_by' => $request->received_by,
                'received_by_other' => $request->received_by_other,
                'status' => $request->status,
                'worker_type' => $request->worker_type,
                // Include notes
                'travel_doc_notes' => $request->travel_doc_notes,
                'settlement_notes' => $request->settlement_notes,
                'graduation_notes' => $request->graduation_notes,
                'reserve_notes' => $request->reserve_notes,
                'reserve_date' => $request->reserve_date,
                'travel_notes' => $request->travel_notes,
            ]
        );

        // List of file fields
        $fileFields = [
            'travel_doc',
            'settlement',
            'graduation',
            'reserve',
            'travel',
        ];

        // Loop to handle file uploads
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $filePath = $request->file($field)->store("uploads/{$field}s", 'public');
                $data->$field = $filePath;  // Set the file path
            }
        }

        // Save the model to update file paths
        $data->save();

        return redirect(route('shelter_workers'))->with('success', __('admin.success'));
    }

    public function edit($id)
    {
        return $this->create($id);
    }

    public function update(Request $request, $id)
    {
        return $this->store($request, $id);
    }

    public function destroy(string $id)
    {
        $data = ShelterWorkers::find($id);
        $data ? $data->delete() : '';
        return redirect()->back()->with('success', __('admin.success'));
    }

    public function download($id, $field)
    {
        $shelterWorker = ShelterWorkers::findOrFail($id);

        if ($shelterWorker->$field) {
            return response()->download(storage_path("app/public/" . $shelterWorker->$field));
        }

        return back()->with('error', __('admin.file_not_found'));
    }

    public function deleteFile($id, $field)
    {
        $shelterWorker = ShelterWorkers::findOrFail($id);

        if ($shelterWorker->$field) {
            // Delete the file from storage
            Storage::disk('public')->delete($shelterWorker->$field);

            // Remove the file path from the database
            $shelterWorker->$field = null;
            $shelterWorker->save();

            return back()->with('success', __('admin.file_deleted'));
        }

        return back()->with('error', __('admin.file_not_found'));
    }


}
