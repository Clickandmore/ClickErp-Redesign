<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\ComplaintType;
use Illuminate\Http\Request;

class ComplaintTypeController extends Controller
{
    public function index()
    {
        $types = ComplaintType::all();
        return view('admin.ingaz.complaint_types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.ingaz.complaint_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        ComplaintType::create(['name' => $request->name]);
        return redirect()->route('complaint-types.index')->with('success', 'تمت إضافة النوع بنجاح');
    }

    public function edit($id)
    {
        $type = ComplaintType::findOrFail($id);
        return view('admin.ingaz.complaint_types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $type = ComplaintType::findOrFail($id);
        $type->update(['name' => $request->name]);
        return redirect()->route('complaint-types.index')->with('success', 'تم تعديل النوع بنجاح');
    }

    public function destroy($id)
    {
        $type = ComplaintType::findOrFail($id);
        $type->delete();
        return jsonSuccess(['url'=>route('complaint-types.index')] , 'تم حذف النوع بنجاح' , 201);
    }
}
