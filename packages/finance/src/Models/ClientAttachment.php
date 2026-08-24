<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientAttachment extends Finance
{
    use HasFactory;
    protected $table = 'fn_client_attachments';
    protected $guarded = [];
}
