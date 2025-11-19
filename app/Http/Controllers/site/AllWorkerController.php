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
            'has_experience_label' => __('ingaz.has_experience_label'),
            'no_experience_label' => __('ingaz.no_experience_label'),
            default => __('ingaz.recruitment_request')
        };

        // dd($request->all());
        
        return view('site.pages.all_workers', $data);
    }

    public function show($id, Request $request, MaidAction $maidAction)
    {
        $maid = $maidAction->find($id);
        if(!$maid){
            return redirect()->route('site.all-workers.index');
        }
        return view('site.pages.cv_details', ['maid' => $maid]);
    }


}
