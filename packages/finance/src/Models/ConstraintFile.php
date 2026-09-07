<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;


class ConstraintFile extends Finance
{
    use AssignsBranch, BranchScoped;

    protected $table = "fn_constraint_files";

    protected $fillable = [
        'branch_id',
        'constraint_id',
        'file',
    ];
}
