<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nami\Finance\Enums\AccountTypesEnum;

/**
 * إدارة بنود «تقرير حركة صندوق اليومية» المربوطة برقم الحساب (fn_daily_cash_reference_lines).
 * الربط لمرة واحدة عبر store مع link=1، والترتيب عبر update مع direction؛ فتبقى الصلاحيات على نمط view/create/edit/delete.
 */
class DailyCashReferenceLineController extends Controller
{
    private const GROUP_LABELS = ['receipts' => 'المقبوضات', 'expenses' => 'المصروفات'];

    public function index()
    {
        $lines = DB::table('fn_daily_cash_reference_lines as l')
            ->leftJoin('fn_accounts as a', 'a.id', '=', 'l.account_id')
            ->orderBy('l.sort_order')
            ->get(['l.*', 'a.code as account_code', 'a.name as account_name'])
            ->groupBy('group_key');

        return view('finance::finance.account_statement.cash_movements.reference_lines', [
            'bladeTitle' => 'بنود تقرير صندوق اليومية',
            'groups' => $this->groupKeys(),
            'groupLabels' => self::GROUP_LABELS,
            'lines' => $lines,
            'linked' => $lines->isNotEmpty(),
            'accounts' => DB::table('fn_accounts')->where('type', '<>', AccountTypesEnum::Client->value)
                ->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->boolean('link')) {
            if (DB::table('fn_daily_cash_reference_lines')->exists()) {
                return back()->withErrors(['link' => 'البنود مربوطة بالفعل.']);
            }
            Artisan::call('finance:link-daily-cash-reference');
            return back()->with('success', 'تم ربط البنود بحساباتها.');
        }

        $this->ensureLinked();
        $data = $request->validate([
            'group_key' => ['required', Rule::in($this->groupKeys())],
            'account_id' => ['required', 'integer', 'exists:fn_accounts,id'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);
        $account = DB::table('fn_accounts')->find($data['account_id']);
        DB::table('fn_daily_cash_reference_lines')->insert([
            'group_key' => $data['group_key'],
            'sort_order' => (int) DB::table('fn_daily_cash_reference_lines')->where('group_key', $data['group_key'])->max('sort_order') + 1,
            'account_id' => $account->id,
            'reference_code' => (string) $account->code,
            'label' => trim((string) ($data['label'] ?? '')) ?: $account->name,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'أُضيف البند.');
    }

    public function update(Request $request, int $id)
    {
        $line = DB::table('fn_daily_cash_reference_lines')->find($id) ?? abort(404);

        if ($request->filled('direction')) {
            $request->validate(['direction' => ['in:up,down']]);
            $up = $request->input('direction') === 'up';
            $neighbor = DB::table('fn_daily_cash_reference_lines')->where('group_key', $line->group_key)
                ->where('sort_order', $up ? '<' : '>', $line->sort_order)
                ->orderBy('sort_order', $up ? 'desc' : 'asc')->first();
            if ($neighbor) {
                DB::transaction(function () use ($line, $neighbor) {
                    DB::table('fn_daily_cash_reference_lines')->where('id', $line->id)->update(['sort_order' => $neighbor->sort_order, 'updated_at' => now()]);
                    DB::table('fn_daily_cash_reference_lines')->where('id', $neighbor->id)->update(['sort_order' => $line->sort_order, 'updated_at' => now()]);
                });
            }
            return back();
        }

        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:fn_accounts,id'],
            'label' => ['required', 'string', 'max:255'],
        ]);
        DB::table('fn_daily_cash_reference_lines')->where('id', $id)->update([
            'account_id' => $data['account_id'], 'label' => trim($data['label']), 'updated_at' => now(),
        ]);

        return back()->with('success', 'حُفظ البند.');
    }

    public function destroy(int $id)
    {
        DB::table('fn_daily_cash_reference_lines')->where('id', $id)->delete();

        return back()->with('success', 'حُذف البند.');
    }

    private function groupKeys(): array
    {
        return array_keys(require __DIR__ . '/../../../config/daily_cash_reference.php');
    }

    private function ensureLinked(): void
    {
        abort_unless(DB::table('fn_daily_cash_reference_lines')->exists(), 422, 'اربط البنود أولاً.');
    }
}
