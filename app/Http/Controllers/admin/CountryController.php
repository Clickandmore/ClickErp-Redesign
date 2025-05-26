<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::ordered()->get();
        return view('admin.countries.index', compact('countries'));
    }

    public function create()
    {
        return view('admin.countries.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|size:2|unique:countries',
            'phone_code' => 'nullable|string|max:10',
            'currency' => 'nullable|string|size:3',
            'currency_symbol' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Country::create($request->all());

        return redirect()->route('countries.index')
            ->with('success', 'تم إضافة الدولة بنجاح');
    }

    public function edit(Country $country)
    {
        return view('admin.countries.edit', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        $validator = Validator::make($request->all(), [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|size:2|unique:countries,code,' . $country->id,
            'phone_code' => 'nullable|string|max:10',
            'currency' => 'nullable|string|size:3',
            'currency_symbol' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $country->update($request->all());

        return redirect()->route('countries.index')
            ->with('success', 'تم تحديث الدولة بنجاح');
    }

    public function destroy(Country $country)
    {
        $country->delete();
        return redirect()->route('countries.index')
            ->with('success', 'تم حذف الدولة بنجاح');
    }

    public function toggleStatus(Country $country)
    {
        $country->update(['is_active' => !$country->is_active]);
        return redirect()->route('countries.index')
            ->with('success', 'تم تحديث حالة الدولة بنجاح');
    }

    public function updateOrder(Request $request)
    {
        $order = $request->input('order');

        foreach ($order as $item) {
            Country::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}
