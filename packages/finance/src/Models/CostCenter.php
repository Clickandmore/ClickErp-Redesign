<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;

use App\Models\auth\Admin;
use Illuminate\Support\Facades\DB;

class CostCenter extends Finance
{
    // الفرع يُسجَّل على الحساب للتقارير، لكن بلا عزل قراءة تلقائي:
    // `code` فريد على مستوى الشجرة كلها، وعشرة مواضع تولّده من
    // `max('code')` — استعلام معزول ينتج كوداً مستعملاً في فرع آخر.
    // عزل أرصدة الفروع يتحقق من عزل القيود والسندات، لا من إخفاء الحسابات.
    use AssignsBranch;

    protected $table ='fn_cost_centers';
    protected $fillable =[
        'branch_id',
        'title', 'parent_id', 'table_name', 'table_related_id', 'code', 'admin_id', 'created_by_id',
        'legacy_source_code', 'legacy_level', 'legacy_full_path', 'legacy_debtor_total', 'legacy_creditor_total',
        'legacy_balance', 'legacy_balance_type', 'legacy_opening_balance',
    ];

    public function children()
    {
        return $this->hasMany(CostCenter::class, 'parent_id', 'id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    public function parent()
    {
        return $this->belongsTo(CostCenter::class, 'parent_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function scopeCanDelete($q)
    {
        $databaseName = config('database.connections.mysql.database'); // Get the current database name

        $tables = DB::select("SELECT table_name FROM information_schema.columns WHERE column_name = 'cost_center_id' AND table_schema = ?", [$databaseName]);

        // Extract table names from the result set
        $tableNames = array_column($tables, 'table_name');
        $count = 0;
        for ($i = 0; $i < count($tableNames); $i++){
            $c = DB::table($tableNames[$i])->where('cost_center_id',$this->attributes['id'])->count();
            if($c > 0) {
                ++$count;
                break;
            }
        }
        return $count;
    }
}
