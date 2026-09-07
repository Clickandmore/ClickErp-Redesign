<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientAttachment extends Finance
{
    use AssignsBranch, BranchScoped;

    use HasFactory;
    protected $table = 'fn_client_attachments';
    protected $guarded = [];
}
