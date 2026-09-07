<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\FindUsLocationAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\FindUsLocationRequest;
use App\Http\Requests\ingaz\FindUsModeRequest;
use App\Models\FindUsLocation;
use App\Support\FindUsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FindUsLocationController extends Controller
{
    public function index(FindUsLocationAction $action): View
    {
        return view('admin.ingaz.find_us.index', [
            'locations' => $action->ordered(),
            'findUsMode' => FindUsSettings::mode(),
            'findUsSingleLink' => FindUsSettings::singleLink(),
        ]);
    }

    /**
     * تبديل وضع عرض الخريطة بين رابط واحد للموقع كله وفرع لكل رابط.
     */
    public function updateMode(FindUsModeRequest $request): RedirectResponse
    {
        FindUsSettings::setMode((string) $request->validated()['find_us_mode']);

        return redirect()
            ->route('find-us.index')
            ->with('success', __('find_us.mode_updated'));
    }

    public function create(): View
    {
        return view('admin.ingaz.find_us.create', [
            'location' => new FindUsLocation([
                'sort_order' => (int) FindUsLocation::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(FindUsLocationRequest $request): RedirectResponse
    {
        FindUsLocation::create($request->validated());

        return redirect()
            ->route('find-us.index')
            ->with('success', __('find_us.created'));
    }

    public function edit(FindUsLocation $location): View
    {
        return view('admin.ingaz.find_us.edit', [
            'location' => $location,
        ]);
    }

    public function update(FindUsLocationRequest $request, FindUsLocation $location): RedirectResponse
    {
        $location->update($request->validated());

        return redirect()
            ->route('find-us.index')
            ->with('success', __('find_us.updated'));
    }

    public function destroy(FindUsLocation $location): RedirectResponse
    {
        $location->delete();

        return redirect()
            ->route('find-us.index')
            ->with('success', __('find_us.deleted'));
    }
}
