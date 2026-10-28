<?php

namespace Nami\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\Constraint;

class DailyCashReferenceService
{
    public function report(?string $from, string $to, string $approval = 'approved'): array
    {
        $accounts = DB::table('fn_accounts')->select('id','parent_id','code','name')->get()->keyBy('id');
        $mapping = $this->definitions($accounts);
        $query = DB::table('fn_constraint_details as d')->join('fn_constraints as c','c.id','=','d.constraint_id')
            ->whereNull('d.deleted_at')->whereNull('c.deleted_at')
            ->where('d.date','<',Carbon::parse($to)->addDay()->toDateString());
        if ($from) $query->where('d.date','>=',$from);
        $allQuery = clone $query;
        if ($approval === 'approved' && Constraint::hasApprovalColumns()) $query->where('c.approval_status',Constraint::APPROVAL_APPROVED);
        $balances = $query->groupBy('d.account_id')->selectRaw('d.account_id, SUM(CAST(d.debtor_value AS DECIMAL(24,2))) - SUM(CAST(d.creditor_value AS DECIMAL(24,2))) as balance')->get()->keyBy('account_id');
        $groups = $issues = $usedIds = [];
        foreach ($mapping as $key=>$lines) {
            $rows = []; $total = 0;
            foreach ($lines as ['code'=>$code, 'label'=>$label, 'matches'=>$matches, 'issue'=>$issue]) {
                $amount = 0;
                $missing = $matches->count() !== 1;
                if ($missing) $issues[] = $issue;
                if (!$missing) {
                    $rootId = $matches->first()->id;
                    foreach ($accounts as $account) {
                        $cursor = $account; $seen = [];
                        while ($cursor && !isset($seen[$cursor->id])) {
                            $seen[$cursor->id] = true;
                            if ($cursor->id === $rootId) {
                                if (isset($usedIds[$account->id])) {
                                    $issues[] = 'تداخل بنود النموذج عند الحساب '.$account->code.'؛ احتسب مرة واحدة فقط';
                                } else {
                                    $usedIds[$account->id] = true;
                                    $amount += $this->minor($balances->get($account->id)?->balance ?? '0');
                                }
                                break;
                            }
                            $cursor = $accounts->get($cursor->parent_id);
                        }
                    }
                }
                $rows[] = ['code'=>$missing ? (string)$code : (string)$matches->first()->code,'name'=>$missing ? $label : $matches->first()->name,'amount'=>$amount,'missing'=>$missing];
                $total += $amount;
            }
            $groups[$key] = compact('rows','total');
        }
        $pending = 0;
        if (Constraint::hasApprovalColumns()) {
            $pending = $allQuery->whereIn('d.account_id',array_keys($usedIds))->where('c.approval_status',Constraint::APPROVAL_PENDING)->distinct()->count('c.id');
        }
        return compact('groups','from','to','approval','pending') + ['issues'=>array_values(array_unique($issues))];
    }

    /**
     * بنود التقرير: من fn_daily_cash_reference_lines (برقم الحساب) إن رُبطت لهذه الشركة،
     * وإلا من ملف الإعدادات بالكود كما كان. [group => [['code','label','matches','issue'], ...]]
     */
    private function definitions($accounts): array
    {
        $linked = Schema::hasTable('fn_daily_cash_reference_lines')
            ? DB::table('fn_daily_cash_reference_lines')->orderBy('group_key')->orderBy('sort_order')->get()
            : collect();

        $groups = [];
        if ($linked->isNotEmpty()) {
            foreach ($linked as $line) {
                $account = $line->account_id ? $accounts->get($line->account_id) : null;
                $groups[$line->group_key][] = [
                    'code' => $line->reference_code, 'label' => $line->label, 'matches' => collect($account ? [$account] : []),
                    'issue' => 'البند «'.$line->label.'»: '.($line->account_id ? 'الحساب المربوط به لم يعد موجوداً' : 'غير مربوط بحساب'),
                ];
            }
            // ترتيب الأقسام كما في ملف الإعدادات
            $order = array_keys(require __DIR__.'/../../config/daily_cash_reference.php');
            $pos = fn ($key) => ($i = array_search($key, $order, true)) === false ? PHP_INT_MAX : $i;
            uksort($groups, fn ($a, $b) => $pos($a) <=> $pos($b));
            return $groups;
        }

        $byCode = $accounts->groupBy(fn ($a) => (string) $a->code);
        foreach (require __DIR__.'/../../config/daily_cash_reference.php' as $key=>$codes) {
            foreach ($codes as $code=>$label) {
                $matches = $byCode->get((string)$code, collect());
                $groups[$key][] = [
                    'code' => (string)$code, 'label' => $label, 'matches' => $matches,
                    'issue' => 'الكود '.$code.': '.($matches->isEmpty() ? 'غير موجود في دليل الحسابات' : 'مكرر في دليل الحسابات'),
                ];
            }
        }
        return $groups;
    }

    private function minor($value): int
    {
        $value = (string)$value;
        [$whole,$fraction] = array_pad(explode('.',ltrim($value,'-+'),2),2,'');
        $amount = (int)$whole*100 + (int)substr(str_pad($fraction,2,'0'),0,2);
        return str_starts_with($value,'-') ? -$amount : $amount;
    }
}
