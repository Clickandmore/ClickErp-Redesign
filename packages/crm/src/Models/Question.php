<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $name
 * @property string $type
 * @property string $created_at
 * @property string $updated_at
 * @property QuestionsAnswer[] $questionsAnswers
 */
class Question extends Model
{
    protected $table = 'crm_questions';
    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */

    /**
     * @var array
     */
    protected $fillable = ['name', 'poll_id','type', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function questionsAnswers()
    {
        return $this->hasMany(QuestionsAnswer::class);
    }

    public function answers()
    {
        return $this->hasMany(QuestionsAnswer::class);
    }
}
