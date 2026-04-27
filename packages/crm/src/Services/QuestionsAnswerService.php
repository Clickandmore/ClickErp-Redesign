<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\QuestionsAnswer;
use App\Services\MainService;

class QuestionsAnswerService extends MainService
{
    public function __construct(QuestionsAnswer $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/QuestionsAnswer/';
    }

    public function storeAnswers($data,$question_id)
    {
        foreach ($data['answers'] as $answer){
            $answer['question_id'] = $question_id;
            parent::store($answer);
        }
    }

    public function updateAnswers($data,$question_id)
    {
        $this->model->where(['question_id'=>$question_id])->delete();
        foreach ($data['answers'] as $answer){
            $answer['question_id'] = $question_id;
            parent::store($answer);
        }
    }
}
