<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\GuidelineSectionAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\GuidelineSectionResource;

class ApiGuidelineController extends Controller
{
    public function index(GuidelineSectionAction $action)
    {
        $section = $action->section();

        return apiResponse(
            true,
            __('api.All items'),
            $section ? new GuidelineSectionResource($section) : [
                'files' => [
                    'client_rights_and_obligations' => null,
                    'worker_rights_and_obligations' => null,
                ],
                'videos' => [],
                'nationalities' => [],
            ],
            200
        );
    }
}
