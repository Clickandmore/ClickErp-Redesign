<?php

namespace App\Http\Controllers\admin;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $query = Country::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('name_en', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('code')) {
            $query->where('code', 'LIKE', "%{$request->code}%");
        }

        if ($request->filled('currency')) {
            $query->where('currency', 'LIKE', "%{$request->currency}%");
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $allCountries = $query->orderBy('sort_order')->paginate(10);

        return view('admin.countries.index', compact('allCountries'));
    }


    public function create(Request $request )
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
        return response()->json([
            'success' => true,
            'message' => 'تم حذف الدولة بنجاح'
        ], 200);

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

        return view('admin.cities.index', compact('cities', 'countries'));
    }

    public function createCity(Request $request)
    {
        $countries = Country::active()->ordered()->get();
        $selectedCountryId = old('country_id', $request->get('country_id'));

        return view('admin.cities.create', compact('countries', 'selectedCountryId'));
    }

    public function storeCity(Request $request)
    {
        $data = $this->validateCity($request);

        City::create($data);

        return redirect()
            ->route('cities.index', ['country_id' => $data['country_id']])
            ->with('success', 'تم إضافة المدينة بنجاح');
    }

    public function editCity(City $city)
    {
        $countries = Country::active()->ordered()->get();

        return view('admin.cities.edit', compact('city', 'countries'));
    }

    public function updateCity(Request $request, City $city)
    {
        $data = $this->validateCity($request, $city);

        $city->update($data);

        return redirect()
            ->route('cities.index', ['country_id' => $data['country_id']])
            ->with('success', 'تم تحديث المدينة بنجاح');
    }

    public function destroyCity(Request $request, City $city)
    {
        $city->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المدينة بنجاح',
            ]);
        }

        return redirect()
            ->route('cities.index')
            ->with('success', 'تم حذف المدينة بنجاح');
    }

    /**
     * API: الحصول على جميع الدول
     */
    public function getCountries()
    {
        $countries = Country::active()
            ->ordered()
            ->get(['id', 'name_ar', 'name_en', 'code', 'phone_code']);

        return response()->json($countries);
    }

    /**
     * API: الحصول على مدن دولة معينة
     */
    public function getCities($countryId)
    {
        $cities = City::where('country_id', $countryId)
            ->active()
            ->ordered()
            ->get(['id', 'name_ar', 'name_en', 'is_capital']);

        return response()->json($cities);
    }

    /**
     * API: البحث في الدول
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        $countries = Country::query()
            ->where(function ($countryQuery) use ($query) {
                $countryQuery->where('name_ar', 'LIKE', "%{$query}%")
                    ->orWhere('name_en', 'LIKE', "%{$query}%")
                    ->orWhere('code', 'LIKE', "%{$query}%");
            })
            ->active()
            ->ordered()
            ->limit(10)
            ->get();

        return response()->json($countries);
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

    private function validateCity(Request $request, ?City $city = null): array
    {
        $validated = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('cities', 'code')->ignore($city?->id),
            ],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'sort_order' => ['nullable', 'integer'],
            'is_capital' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_capital'] = $request->boolean('is_capital');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
