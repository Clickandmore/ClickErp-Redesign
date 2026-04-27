<?php

namespace Nami\Crm\Services;

use Nami\Crm\Models\QuestionsAnswer;
use App\Services\MainService;

class QuestionAnswerService extends MainService
{
    public function __construct(QuestionsAnswer $model)
    {
        $this->model = $model;
        $this->fileFolder = 'images/Question/';
    }


}
