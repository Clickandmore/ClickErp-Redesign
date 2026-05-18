<?php

namespace App\Models\ingaz;

use Illuminate\Database\Eloquent\Model;

class AgentMaidBulkImportRow extends Model
{
    protected $fillable = [
        'agent_maid_bulk_import_id',
        'row_number',
        'status',
        'maid_id',
        'imported_at',
        'raw_data',
        'normalized_data',
        'error_messages',
    ];

    protected $casts = [
        'imported_at'     => 'datetime',
        'raw_data'        => 'array',
        'normalized_data' => 'array',
        'error_messages'  => 'array',
    ];

    public function import()
    {
        return $this->belongsTo(AgentMaidBulkImport::class, 'agent_maid_bulk_import_id');
    }

    public function maid()
    {
        return $this->belongsTo(Maid::class);
    }
}
