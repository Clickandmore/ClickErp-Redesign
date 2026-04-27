<?php

namespace Nami\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAttachment extends Model
{
    use HasFactory;
    protected $table = 'fn_client_attachments';
    protected $guarded = [];
}
