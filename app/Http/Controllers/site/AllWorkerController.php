<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AllWorkerController extends Controller
{
    public function index(Request $request,MaidAction $maidAction)
    {
        $data['maids'] = $maidAction->paginate($request);
        $data['type'] = $request->type ?? 'all';
        return view('site.pages.all_workers', $data);
    }


}
