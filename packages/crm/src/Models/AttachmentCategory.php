<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class AttachmentCategory extends Model
{
    use HasFactory;
    protected $table = 'crm_attachment_categories';
    protected $fillable = ['title'];
}
