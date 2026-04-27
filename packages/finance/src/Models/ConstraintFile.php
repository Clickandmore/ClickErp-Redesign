<?php

namespace Nami\Finance\Models;


class ConstraintFile extends Finance
{
    protected $table = "fn_constraint_files";

    protected $fillable = [
        'constraint_id',
        'file',
    ];
}
