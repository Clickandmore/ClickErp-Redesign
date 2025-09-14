<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $query = Country::query();

        // فلترة حسب البحث في الاسم
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%");
            });
        }

        // فلترة حسب رمز الدولة
        if ($request->filled('code')) {
            $query->where('code', 'LIKE', "%{$request->code}%");
        }

        // فلترة حسب العملة
        if ($request->filled('currency')) {
            $query->where('currency', 'LIKE', "%{$request->currency}%");
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        $countries = $query->ordered()->get();
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

    public function show(Country $country)
    {
        $cities = $country->cities()->ordered()->get();
        return view('admin.countries.show', compact('country', 'cities'));
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

    /**
     * عرض جميع المدن مع فلترة
     */
    public function citiesIndex(Request $request)
    {
        $query = City::with('country');

        // فلترة حسب الدولة
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // فلترة حسب الاسم
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%");
            });
        }

        // فلترة حسب العاصمة
        if ($request->filled('is_capital')) {
            $query->where('is_capital', $request->boolean('is_capital'));
        }

        // فلترة حسب الحالة
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $cities = $query->ordered()->paginate(20);
        $countries = Country::active()->ordered()->get();

        return view('cities.index', compact('cities', 'countries'));
    }

    /**
     * API: الحصول على جميع المدن
     */
    public function getAllCities(Request $request)
    {
        $query = City::with('country');

        // فلترة حسب الدولة
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // فلترة حسب الاسم
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%");
            });
        }

        // فلترة حسب العاصمة
        if ($request->filled('is_capital')) {
            $query->where('is_capital', $request->boolean('is_capital'));
        }

        $cities = $query->active()->ordered()->get(['id', 'name_ar', 'name_en', 'country_id', 'is_capital']);

        return response()->json($cities);
    }

    /**
     * API: الحصول على تفاصيل مدينة واحدة
     */
    public function getCityDetails($cityId)
    {
        $city = City::with('country')->find($cityId);

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'المدينة غير موجودة'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'city' => $city
        ]);
    }

    /**
     * API: البحث في المدن
     */
    public function searchCities(Request $request)
    {
        $query = $request->get('q');
        $countryId = $request->get('country_id');

        $cities = City::with('country')
                     ->where(function($q) use ($query) {
                         $q->where('name_ar', 'LIKE', "%{$query}%")
                           ->orWhere('name_en', 'LIKE', "%{$query}%");
                     });

        if ($countryId) {
            $cities->where('country_id', $countryId);
        }

        $cities = $cities->active()->limit(10)->get();

        return response()->json($cities);
    }
}
