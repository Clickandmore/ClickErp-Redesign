<?php

namespace App\Http\Controllers\Hourly;

use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyCustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * «مجموعات العملاء» تحت التأجير بالساعة > العملاء. الحذف يترك أعضاءها بلا مجموعة (المفتاح nullOnDelete).
 * نسبة الخصم تُحفظ وتُعرض للمجموعة؛ لا تُطبَّق على تسعير الطلبات حاليًا.
 */
class CustomerGroupController extends Controller
{
    public function index()
    {
        return view('admin.hourly_customers.groups', [
            'groups' => HourlyCustomerGroup::withCount(['profiles' => fn ($q) => $q->whereNull('deleted_at')])->orderBy('name')->get(),
            'canManage' => AdminGuardHasAnyPerm(['hourly_customer_groups_create', 'hourly_customer_groups_edit', 'hourly_customer_groups_delete']),
            'canCreate' => AdminGuardHasPerm('hourly_customer_groups_create'),
            'canEdit' => AdminGuardHasPerm('hourly_customer_groups_edit'),
            'canDelete' => AdminGuardHasPerm('hourly_customer_groups_delete'),
        ]);
    }

    public function store(Request $request)
    {
        HourlyCustomerGroup::create($this->validated($request, null));

        return redirect()->route('admin.hourly-customer-groups.index')->with('success', __('hourly_customers.groups.saved'));
    }

    public function update(Request $request, HourlyCustomerGroup $group)
    {
        $group->update($this->validated($request, $group));

        return redirect()->route('admin.hourly-customer-groups.index')->with('success', __('hourly_customers.groups.saved'));
    }

    public function destroy(HourlyCustomerGroup $group)
    {
        $group->delete();

        return redirect()->route('admin.hourly-customer-groups.index')->with('success', __('hourly_customers.groups.deleted'));
    }

    private function validated(Request $request, ?HourlyCustomerGroup $group): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('hourly_customer_groups', 'name')->ignore($group?->id)],
            'description' => 'nullable|string|max:1000', 'discount_percent' => 'nullable|numeric|min:0|max:100', 'is_active' => 'nullable|boolean',
        ]);

        return $data + ['is_active' => $request->boolean('is_active')];
    }
}
