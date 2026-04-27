<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Hr\Models\Allowance;

class getAllowanceDataController extends Controller
{
    public function getAllowanceData($id) {
        $allowance = Allowance::find($id);

        return response()->json([
            'value' => $allowance->value,
            'type' => $allowance->type
        ]);
    }
}
