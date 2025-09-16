<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AllWorkerController extends Controller
{
    public function index(Request $request,MaidAction $maidAction, JobTitleAction $jobTitleAction, NationalityAction $nationalityAction)
    {
        if($request->ajax()){
            $maids = $maidAction->paginate($request);
            $html = view('site.pages.maids_list', ['maids' => $maids])->render();
            return $html;
        }

        $data['maids'] = $maidAction->paginate($request);
        $data['type'] = $request->type ?? 'all';
        $data['jobTitles'] = $jobTitleAction->get();
        $data['nationalities'] = $nationalityAction->get();
        $data['blade_title'] = match($request->type){
            'all' => __('ingaz.recruitment_request'),
            'estqdam' => __('ingaz.recruitment_request'),
            'egar' => __('ingaz.labor_rental_request'),
            'transfer' => __('ingaz.Labor_transfer_request'),
            default => __('ingaz.recruitment_request')
        };
        return view('site.pages.all_workers', $data);
    }


}
