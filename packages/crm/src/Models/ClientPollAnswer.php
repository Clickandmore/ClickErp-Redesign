<?php

namespace Nami\Crm\Models;

use Nami\Crm\Models\Poll;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPollAnswer extends Model
{
    use HasFactory;
    protected $table = "crm_client_poll_answers";
    protected $fillable = ["client_id", "poll_id", "question_id", "answer_id", "essay_answer"];


    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }

    public function poll()
    {
        return $this->belongsTo(Poll::class,'poll_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class,'question_id');
    }

    public function answer()
    {
        return $this->belongsTo(QuestionsAnswer::class,'answer_id');
    }

}
