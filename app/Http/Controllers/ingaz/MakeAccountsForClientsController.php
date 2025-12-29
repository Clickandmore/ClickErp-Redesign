<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;

class MakeAccountsForClientsController extends Controller
{
    public function store(Request $request)
    {
        return DB::transaction(function () {
            $parentAccount = Account::where('type', AccountTypesEnum::Clients->value)->lockForUpdate()->firstOrFail();
            $lastCode = Account::where('parent_id', $parentAccount->id)->max('code');
            if (!$lastCode) {
                $lastCode = (int)($parentAccount->code . '00000');
            }
            foreach (User::whereNull('account_id')->lazy() as $client) {
                $lastCode++;
                $newAccount = Account::create([
                    'name' => $client->name,
                    'operation_type' => $parentAccount->operation_type,
                    'status' => $parentAccount->status,
                    'level' => $parentAccount->level + 1,
                    'type' => AccountTypesEnum::Client->value,
                    'parent_id' => $parentAccount->id,
                    'code' => $lastCode,
                    'created_by_id' => Auth::id(),
                ]);
                $client->update([
                    'account_id' => $newAccount->id
                ]);
            }
            return jsonSuccess(null, __("ingaz.Accounts were created for customers"), null);
        });
    }

}
