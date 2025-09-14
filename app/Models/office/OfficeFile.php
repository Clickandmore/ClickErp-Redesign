<?php

namespace App\Models\office;

use App\Models\ingaz\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeFile extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'office_files';
    protected $casts = [
        'passport_issuance_location' => 'array',
        'education' => 'array',
        'languages' => 'array',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'recruitment_office_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'office_file_skills', 'office_file_id', 'skill_id');
    }
}
