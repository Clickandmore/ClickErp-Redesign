<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaidBulkImportRow extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;

    protected $fillable = [
        'maid_bulk_import_id',
        'row_number',
        'status',
        'maid_id',
        'imported_at',
        'raw_data',
        'normalized_data',
        'error_messages',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'raw_data' => 'array',
        'normalized_data' => 'array',
        'error_messages' => 'array',
    ];

    public function import()
    {
        return $this->belongsTo(MaidBulkImport::class, 'maid_bulk_import_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }
}
