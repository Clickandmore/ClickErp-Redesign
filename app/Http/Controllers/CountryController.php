<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\City;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * عرض جميع الدول
     */
    public function index()
    {
        $countries = Country::active()->ordered()->get();
        return view('countries.index', compact('countries'));
    }

    /**
     * عرض تفاصيل دولة مع مدنها
     */
    public function show($id)
    {
        $country = Country::with(['cities' => function($query) {
            $query->active()->ordered();
        }])->findOrFail($id);

        return view('countries.show', compact('country'));
    }

    /**
     * API: الحصول على جميع الدول
     */
    public function getCountries()
    {
        $countries = Country::active()->ordered()->get(['id', 'name_ar', 'name_en', 'code', 'phone_code']);
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

        $countries = Country::where('name_ar', 'LIKE', "%{$query}%")
                           ->orWhere('name_en', 'LIKE', "%{$query}%")
                           ->orWhere('code', 'LIKE', "%{$query}%")
                           ->active()
                           ->limit(10)
                           ->get();

        return response()->json($countries);
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
