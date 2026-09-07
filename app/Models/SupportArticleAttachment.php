<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportArticleAttachment extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use HasFactory;

    protected $fillable = [
        'article_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    public function article()
    {
        return $this->belongsTo(SupportKnowledgeArticle::class, 'article_id');
    }
}
