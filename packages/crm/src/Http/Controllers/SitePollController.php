<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserPoll;

use App\Models\User;
use Illuminate\Http\Request;
use Nami\Crm\Models\Client;
use Nami\Crm\Models\ClientPoll;
use Nami\Crm\Models\ClientPollAnswer;
use Nami\Crm\Models\Poll;
use Nami\Crm\Models\Question;

class SitePollController extends Controller
{
    public function index($email, $poll_id)
    {

        $poll = Poll::findOrFail($poll_id);

        $questions = Question::with('answers')
            ->where('poll_id', $poll_id)
            ->where('is_show', 1)
            ->latest()
            ->get();

        $user = Client::where('email', $email)->firstOrFail();

        $type = "client";
        $pollClient = ClientPoll::where('client_id', $user->id)->where('poll_id', $poll_id)->first();
        if (!$user)
            abort(404);

        if ($pollClient->status == 'ended')
            abort(499);


        return view('web.client-poll', compact('questions', 'poll', 'user', 'type'));
    }

    public function indexUser($email, $poll_id)
    {
        $poll = Poll::findOrFail($poll_id);

        $questions = Question::with('answers')
            ->where('poll_id', $poll_id)
            ->where('is_show', 1)
            ->latest()
            ->get();
        $type = "user";
        $user = User::where('email', $email)->firstOrFail();

        $pollClient = UserPoll::where('user_id', $user->id)->where('poll_id', $poll_id)->first();
        if (!$user)
            abort(404);

        if ($pollClient->status == 'ended')
            abort(499);


        return view('frontend.client-poll', compact('questions', 'poll', 'user', 'type'));
    }


    public function submitAnswers(request $request)
    {
        // dd($request->type);
        if (isset($request->type) && $request->type == 'user') {
            $user = User::where('email', $request->email)->firstOrFail();
            $pollClient = UserPoll::where('user_id', $user->id)->where('poll_id', $request->identifier)->first();
        } else {
            $user = Client::where('email', $request->email)->firstOrFail();
            $pollClient = ClientPoll::where('client_id', $user->id)->where('poll_id', $request->identifier)->first();
        }
        // dd($pollClient);
        if ($pollClient) {
            foreach ($request->answers as $ans) {
                $questionType = Question::find($ans['question_id'])->type;

                $answerId = null;
                $essayAnswer = null;

                if ($questionType == 'choices') {
                    $answerId = $ans['answer'];
                } else {
                    $essayAnswer = $ans['answer'];
                }

                ClientPollAnswer::create([
                    'client_id' => $user->id,
                    'question_id' => $ans['question_id'],
                    'answer_id' => $answerId,
                    'essay_answer' => $essayAnswer,
                    'poll_id' => $request->identifier,
                ]);
            }

            // Update the poll status to 'done'
            $pollClient->update(['status' => 'ended']);

            $viewContent = view('frontend.complete')->render();
            $responseData = ['view' => $viewContent, 'message' => 'Answers submitted successfully'];

            return response()->json($responseData, 200);
        } else {
            return "حدث خطأ";
        }
    }

    public function indexShow($poll_id)
    {
        $poll = Poll::findOrFail($poll_id);

        $questions = Question::with('answers')
            ->where('poll_id', $poll_id)
            ->where('is_show', 1)
            ->latest()
            ->get();

//        $user = Client::where('email',$email)->firstOrFail();

//        $pollClient = ClientPoll::where('client_id',$user->id)->where('poll_id',$poll_id)->first();
//        if (!$user)
//            abort(404);
//
//        if ($pollClient->status == 'ended')
//            abort(499);


        return view('web.client-poll-show', compact('questions', 'poll'));
    }


    public function submitAnswersShow(request $request)
    {

        $viewContent = view('web.complete')->render();
        $responseData = ['view' => $viewContent, 'message' => 'Answers submitted successfully'];

        return response()->json($responseData, 200);
//        else{
//            return "حدث خطأ";
//        }


    }
}
