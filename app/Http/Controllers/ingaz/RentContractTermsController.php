<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\RentContractTerm;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RentContractTermsController extends Controller
{
    public $folderPath = "admin.ingaz.RentContractTerms";
    public $mainRoute = "rent-contract-terms";

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $terms = RentContractTerm::query()->latest();

            return DataTables::eloquent($terms)
                ->addIndexColumn()
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">نشط</span>'
                        : '<span class="badge bg-secondary">غير نشط</span>';
                })
                ->editColumn('content', function ($row) {
                    return \Str::limit($row->content, 100);
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cogs"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu">';

                    $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '">
                                    <i class="fas fa-edit"></i> تعديل
                                </a></li>';

                    $actions .= '<li><button class="dropdown-item text-danger delete-btn" data-id="' . $row->id . '">
                                    <i class="fas fa-trash"></i> حذف
                                </button></li>';

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['is_active', 'actions'])
                ->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        return view($this->folderPath . '.index', $data);
    }

    public function create()
    {
        return view($this->folderPath . '.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        RentContractTerm::create([
            'title' => $request->title,
            'content' => $request->content,
            'order' => $request->order ?? 0,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route($this->mainRoute . '.index')
                        ->with('success', 'تم إضافة البند بنجاح');
    }

    public function show($id)
    {
        $term = RentContractTerm::findOrFail($id);
        return view($this->folderPath . '.show', compact('term'));
    }

    public function edit($id)
    {
        $term = RentContractTerm::findOrFail($id);
        return view($this->folderPath . '.edit', compact('term'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'order' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        $term = RentContractTerm::findOrFail($id);

        $term->update([
            'title' => $request->title,
            'content' => $request->content,
            'order' => $request->order ?? 0,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route($this->mainRoute . '.index')
                        ->with('success', 'تم تحديث البند بنجاح');
    }

    public function destroy($id)
    {
        try {
            $term = RentContractTerm::findOrFail($id);
            $term->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف البند بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف: ' . $e->getMessage()
            ], 500);
        }
    }
}
