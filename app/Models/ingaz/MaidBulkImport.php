<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaidBulkImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_by',
        'approved_by',
        'status',
        'original_filename',
        'uploaded_file_path',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'imported_rows',
        'failed_rows',
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
        return $this->hasMany(MaidBulkImportRow::class)->orderBy('row_number');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(Admin::class, 'uploaded_by');
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
            'pending_review' => 'بانتظار المراجعة',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'completed_with_errors' => 'مكتمل مع ملاحظات',
            'failed' => 'فشل',
        ][$this->status] ?? $this->status;
    }
}
