<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\airport as Airport;
use App\Models\City;
use App\Models\Country;
use App\Support\TravelAirports;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AirportController extends Controller
{
    private string $folderPath = 'admin.ingaz.airports';
    private string $mainRoute = 'airports';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = TravelAirports::datatableRows();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('created_at', fn ($row) => optional($row->created_at)->format('Y-m-d h:i A'))
                ->addColumn('actions', function ($row) {
                    return editButton(route($this->mainRoute . '.edit', $row->id), $row->name)
                        . ' '
                        . deleteButton(route($this->mainRoute . '.destroy', $row->id));
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view($this->folderPath . '.index');
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . '.create', $this->formData())->render(),
            ]);
        }

        abort(404);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        Airport::create($data);

        return jsonSuccess();
    }

    public function edit(Request $request, Airport $airport)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . '.edit', $this->formData() + ['obj' => $airport])->render(),
            ]);
        }

        abort(404);
    }

    public function update(Request $request, Airport $airport)
    {
        $airport->update($this->validatedData($request));

        return jsonSuccess();
    }

    public function destroy(Airport $airport)
    {
        $airport->delete();

        return jsonSuccess();
    }

    private function formData(): array
    {
        return [
            'countries' => Country::query()->ordered()->get(),
            'cities' => City::query()->ordered()->get(),
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
        ]);
    }
}
