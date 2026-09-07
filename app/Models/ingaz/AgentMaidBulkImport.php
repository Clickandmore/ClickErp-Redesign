<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class AgentMaidBulkImport extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    protected $fillable = [
        'agent_id',
        'office_id',
        'approved_by',
        'status',
        'file_type',
        'original_filename',
        'uploaded_file_path',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'imported_rows',
        'failed_rows',
        'rejection_reason',
        'approved_at',
        'processed_at',
        'meta',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function rows()
    {
        return $this->hasMany(AgentMaidBulkImportRow::class)->orderBy('row_number');
    }

    public function office()
    {
        return $this->belongsTo(\App\Models\RecruitmentOffices::class, 'office_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function getCanApproveAttribute(): bool
    {
        return in_array($this->status, ['pending_review', 'completed_with_errors'], true)
            && $this->rows()->where('status', 'pending')->exists();
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'pending_review'      => 'بانتظار المراجعة',
            'processing'          => 'قيد المعالجة',
            'completed'           => 'مكتمل',
            'completed_with_errors' => 'مكتمل مع ملاحظات',
            'failed'              => 'فشل',
            'rejected'            => 'مرفوض',
        ][$this->status] ?? $this->status;
    }

    public function getFileTypeIconAttribute(): string
    {
        return match ($this->file_type) {
            'excel' => 'fa-file-excel',
            'word'  => 'fa-file-word',
            'pdf'   => 'fa-file-pdf',
            default => 'fa-file',
        };
    }
}
