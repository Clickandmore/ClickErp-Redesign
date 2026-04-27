<?php

namespace Nami\Crm\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $question_id
 * @property string $value
 * @property string $color
 * @property string $created_at
 * @property string $updated_at
 * @property Question $question

 */
class QuestionsAnswer extends Model
{
    protected $table = 'crm_questions_answers';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */


    /**
     * @var array
     */
    protected $fillable = ['question_id', 'value', 'created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

}
