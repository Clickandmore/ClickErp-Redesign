<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query()->ordered();

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $branches = $query->paginate(20);

        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function settings()
    {
        return view('admin.branches.settings', [
            'branchesCount' => Branch::query()->count(),
            'activeBranchesCount' => Branch::query()->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['code'] = $data['code'] ?: $this->generateCode();

        Branch::create($data);

        return redirect()
            ->route('branches.index')
            ->with('success', 'تم إضافة الفرع بنجاح');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->validatedData($request, $branch));

        return redirect()
            ->route('branches.index')
            ->with('success', 'تم تحديث الفرع بنجاح');
    }

    public function destroy(Request $request, Branch $branch)
    {
        $branch->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفرع بنجاح',
            ]);
        }

        return redirect()
            ->route('branches.index')
            ->with('success', 'تم حذف الفرع بنجاح');
    }

    private function validatedData(Request $request, ?Branch $branch = null): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('branches', 'code')->ignore($branch?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.is_closed' => ['nullable', 'boolean'],
            'working_hours.*.from' => ['nullable', 'date_format:H:i'],
            'working_hours.*.to' => ['nullable', 'date_format:H:i'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['working_hours'] = $this->normalizeWorkingHours($data['working_hours'] ?? []);

        return $data;
    }

    private function generateCode(): string
    {
        $lastId = (int) Branch::query()->max('id');

        do {
            $code = 'BR-' . str_pad((string) (++$lastId), 4, '0', STR_PAD_LEFT);
        } while (Branch::query()->where('code', $code)->exists());

        return $code;
    }

    private function normalizeWorkingHours(array $hours): array
    {
        $days = [
            'saturday',
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
        ];

        return collect($days)
            ->mapWithKeys(function (string $day) use ($hours) {
                $row = $hours[$day] ?? [];
                $isClosed = (bool) ($row['is_closed'] ?? false);

                return [$day => [
                    'is_closed' => $isClosed,
                    'from' => $isClosed ? null : ($row['from'] ?? null),
                    'to' => $isClosed ? null : ($row['to'] ?? null),
                ]];
            })
            ->all();
    }
}
