<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\TransferContractTerm;
use DataTables;
use Illuminate\Http\Request;

class TransferContractTermsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $terms = TransferContractTerm::select(['id', 'section_title', 'section_order', 'term_number', 'term_content', 'page_number', 'is_active'])
                ->orderBy('section_order')
                ->orderBy('term_order')
                ->get();

            return DataTables::of($terms)
                ->addIndexColumn()
                ->addColumn('section_info', function ($term) {
                    return $term->section_title . ' (ترتيب: ' . $term->section_order . ')';
                })
                ->addColumn('term_info', function ($term) {
                    return $term->term_number ? 'البند رقم ' . $term->term_number : 'بدون رقم';
                })
                ->addColumn('content_preview', function ($term) {
                    return \Str::limit($term->term_content, 100);
                })
                ->addColumn('status', function ($term) {
                    return $term->is_active
                        ? '<span class="badge bg-success">نشط</span>'
                        : '<span class="badge bg-danger">غير نشط</span>';
                })
                ->addColumn('actions', function ($term) {
                    return '
                        <div class="btn-group" role="group">
                            <a href="' . route('transfer-contract-terms.edit', $term->id) . '" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTerm(' . $term->id . ')">
                                <i class="fas fa-trash"></i> حذف
                            </button>
                        </div>
                    ';
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }

        return view('admin.ingaz.TransferContractTerms.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ingaz.TransferContractTerms.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'section_title' => 'required|string|max:255',
            'section_order' => 'required|integer|min:1',
            'term_content' => 'required|string',
            'term_order' => 'required|integer|min:1',
            'page_number' => 'required|integer|min:1',
        ]);

        TransferContractTerm::create([
            'section_title' => $request->section_title,
            'section_order' => $request->section_order,
            'term_number' => $request->term_number,
            'term_content' => $request->term_content,
            'term_order' => $request->term_order,
            'page_number' => $request->page_number,
            'is_active' => $request->has('is_active'),
        ]);
        return jsonSuccess(['redirect' => route('transfer-contract-terms.index')], 'تم اضافة البند بنجاح');

//        return redirect()->route('transfer-contract-terms.index')
//            ->with('success', 'تم إضافة البند بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransferContractTerm $transferContractTerm)
    {
        return view('admin.ingaz.TransferContractTerms.show', compact('transferContractTerm'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransferContractTerm $transferContractTerm)
    {
        return view('admin.ingaz.TransferContractTerms.edit', compact('transferContractTerm'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransferContractTerm $transferContractTerm)
    {
        $request->validate([
            'section_title' => 'required|string|max:255',
            'section_order' => 'required|integer|min:1',
            'term_content' => 'required|string',
            'term_order' => 'required|integer|min:1',
            'page_number' => 'required|integer|min:1',
        ]);

        $transferContractTerm->update([
            'section_title' => $request->section_title,
            'section_order' => $request->section_order,
            'term_number' => $request->term_number,
            'term_content' => $request->term_content,
            'term_order' => $request->term_order,
            'page_number' => $request->page_number,
            'is_active' => $request->has('is_active'),
        ]);

        return jsonSuccess(['redirect' => route('transfer-contract-terms.index')], 'تم تحديث البند بنجاح');

//        return redirect()->route('transfer-contract-terms.index')
//            ->with('success', 'تم تحديث البند بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransferContractTerm $transferContractTerm)
    {
        $transferContractTerm->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف البند بنجاح'
        ]);
    }
}
