<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataImportBatch extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;

    protected $guarded = [];
    protected $casts = ['options' => 'array', 'result' => 'array', 'approved_at' => 'datetime'];

    public function rows() { return $this->hasMany(DataImportRow::class, 'batch_id'); }
    public function uploadedBy() { return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by')->withTrashed(); }
    public function approvedBy() { return $this->belongsTo(\App\Models\auth\Admin::class, 'approved_by')->withTrashed(); }
}
