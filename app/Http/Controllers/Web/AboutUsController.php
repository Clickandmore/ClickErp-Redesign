<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\AboutUsAction;
use App\Http\Actions\ingaz\ContactUsAction;
use App\Http\Actions\web\HomeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\api\ContactUs\StoreContactUsRequest;
use App\Models\ingaz\Feature;
use Illuminate\Http\Request;

class AboutUsController extends Controller
{


    public function index(AboutUsAction $action) {
        $data = $action->getWeb();
        return view('web.ingaz.about_us',$data);
    }




}
