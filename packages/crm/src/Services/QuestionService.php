<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\Question;
use Nami\Crm\Models\QuestionsAnswer;
use App\Services\MainService;
use Illuminate\Support\Facades\DB;

class QuestionService extends MainService
{
    public function __construct(Question $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Question/';
    }

    public function storeQuestion($data)
    {
        DB::beginTransaction();
        try {
//            dd($data);
            $obj = parent::store($data);
            if($data['type'] == 'choices') {
                $answer = new QuestionsAnswer();
                $answerAction = new QuestionsAnswerService($answer);
                $answerAction->storeAnswers($data, $obj->id);
            }
            DB::commit();
           return $obj;
        } catch (\Exception $e) {
            DB::rollback();
            return false;
        }
    }

    public function updateQuestion($id,$data)
    {
        DB::beginTransaction();
        try {
            if($data['type'] == 'choices') {
                $answer = new QuestionsAnswer();
                $answerAction = new QuestionsAnswerService($answer);
                $answerAction->updateAnswers($data, $id);
                unset($data['answers']);
            }
            $obj = parent::update($id, $data);

            DB::commit();
            return $obj;
        } catch (\Exception $e) {
            DB::rollback();
            return false;
        }
    }
}
