<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Support\{HourlyAppReviewAccounts as Review, HourlyStaffOtp};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class HourlyAppReviewAccountsController extends Controller
{
    public function index(Request $request)
    {
        $settings = Review::all();
        $options = [];
        foreach (Review::ROLES as $role => $label) {
            $query = Review::query($role);
            if (!Schema::hasTable($query->getModel()->getTable())) {
                $options[$role] = collect();
                continue;
            }
            $search = trim((string) $request->query('search', ''));
            if ($search !== '') {
                $query->where(function ($q) use ($role, $search) {
                    $q->where('name', 'like', '%'.$search.'%')->orWhere('id', ctype_digit($search) ? (int) $search : 0);
                    foreach (match ($role) { 'customer' => ['phone'], 'driver' => ['phone', 'login_phone'], 'worker' => ['hourly_login_phone'] } as $column) {
                        $q->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            }
            $options[$role] = $query->orderByDesc('id')->limit(50)->get();
            $selected = Review::query($role)->find($settings['accounts'][$role]['account_id'] ?? 0);
            if ($selected && !$options[$role]->contains('id', $selected->id)) $options[$role]->prepend($selected);
        }
        return view('admin.hourly_app_review_accounts.index', compact('settings', 'options'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'accounts' => 'required|array:customer,worker,driver|size:3',
            'accounts.*' => 'required|array:account_id,enabled,code',
            'accounts.*.account_id' => 'nullable|integer|min:1',
            'accounts.*.enabled' => 'required|boolean',
            'accounts.*.code' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
        ]);
        $current = Review::all();
        $settings = ['enabled' => $request->boolean('enabled'), 'accounts' => []];
        foreach (Review::ROLES as $role => $label) {
            $entry = $data['accounts'][$role];
            $entry['enabled'] = (bool) $entry['enabled'];
            $entry['code'] = $entry['code'] ?? ($current['accounts'][$role]['code'] ?? null);
            $entry['account_id'] = !empty($entry['account_id']) ? (int) $entry['account_id'] : null;
            $account = $entry['account_id'] ? Review::query($role)->find($entry['account_id']) : null;
            if ($settings['enabled'] && $entry['enabled'] && $entry['account_id'] && !$account) throw ValidationException::withMessages(["accounts.$role.account_id" => "حساب $label غير موجود أو محذوف."]);
            $entry['phone'] = $account ? HourlyStaffOtp::normalize(Review::phone($role, $account)) : '';
            if ($settings['enabled'] && $entry['enabled'] && (!$account || !Review::available($role, $account) || !preg_match('/^05[0-9]{8}$/', $entry['phone']))) {
                $message = $role === 'customer'
                    ? 'اختر حساب عميل غير محظور، برقم من 9 أرقام يبدأ بـ5 ومفتاح دولة 966 أو +966. لا يشترط إنشاء الحساب من التطبيق.'
                    : "اختر حساب $label متاحًا للجوال وله رقم سعودي صحيح. راجع حالة الحساب وصلاحية وثائق السائق.";
                throw ValidationException::withMessages(["accounts.$role.account_id" => $message]);
            }
            if ($settings['enabled'] && $entry['enabled'] && !$entry['code']) throw ValidationException::withMessages(["accounts.$role.code" => "أدخل رمزًا من خمسة أرقام لحساب $label."]);
            $settings['accounts'][$role] = $entry;
        }
        Review::persist($settings);
        return redirect()->route('dev-superadmin.hourly-app-review-accounts.index')->with('success', 'تم حفظ حسابات اختبار التطبيقات.');
    }
}
