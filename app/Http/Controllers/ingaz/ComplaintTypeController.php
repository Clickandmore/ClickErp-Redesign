<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\ComplaintType;
use Illuminate\Http\Request;

class ComplaintTypeController extends Controller
{
    public function index()
    {
        $types = ComplaintType::query()->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.ingaz.complaint_types.index', compact('types'));
    }

    public function create()
    {
        $partyTypes = ComplaintType::partyTypes();
        return view('admin.ingaz.complaint_types.create', compact('partyTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:complaint_types,name',
            'complainant_type' => 'required|in:' . implode(',', array_keys(ComplaintType::partyTypes())),
            'against_type' => 'required|in:' . implode(',', array_keys(ComplaintType::partyTypes())),
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        ComplaintType::create($request->only('name', 'complainant_type', 'against_type', 'description', 'sort_order') + [
            'is_active' => $request->boolean('is_active'),
        ]);
        return redirect()->route('complaint-types.index')->with('success', 'تمت إضافة النوع بنجاح');
    }

    public function edit($id)
    {
        $type = ComplaintType::findOrFail($id);
        $partyTypes = ComplaintType::partyTypes();
        return view('admin.ingaz.complaint_types.edit', compact('type', 'partyTypes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:complaint_types,name,' . $id,
            'complainant_type' => 'required|in:' . implode(',', array_keys(ComplaintType::partyTypes())),
            'against_type' => 'required|in:' . implode(',', array_keys(ComplaintType::partyTypes())),
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $type = ComplaintType::findOrFail($id);
        $type->update($request->only('name', 'complainant_type', 'against_type', 'description', 'sort_order') + [
            'is_active' => $request->boolean('is_active'),
        ]);
        return redirect()->route('complaint-types.index')->with('success', 'تم تعديل النوع بنجاح');
    }

    public function destroy($id)
    {
        $type = ComplaintType::findOrFail($id);
        $type->delete();
        return jsonSuccess(['url'=>route('complaint-types.index')] , 'تم حذف النوع بنجاح' , 201);
    }
}
