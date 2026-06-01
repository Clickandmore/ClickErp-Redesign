<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportKnowledgeArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'summary',
        'content',
        'video_url',
        'is_published',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function attachments()
    {
        return $this->hasMany(SupportArticleAttachment::class, 'article_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }
}
