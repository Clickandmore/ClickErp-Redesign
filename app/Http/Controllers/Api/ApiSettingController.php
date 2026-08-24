<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\SettingPoliciesResource;
use App\Http\Resources\Api\SettingResource as objResource;
use App\Http\Traits\API\ApiResponse;
use App\Models\ingaz\Setting as objModel;
use App\Models\NewSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ApiSettingController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
       $data = objModel::with('translations')->first();
       if (isset($data->id)) {
            $settings = objResource::make($data);
            return $this->successResponse($settings, __('api.All items'), 200);
       } else {
            return $this->successResponse(null, __('api.not found'), 201);
       }
    }

    public function policies()
    {
        $data = objModel::with('translations')->first();

        if (isset($data->id)) {
            return $this->successResponse(
                SettingPoliciesResource::make($data),
                __('api.All items'),
                200
            );
        }

        return $this->successResponse(null, __('api.not found'), 201);
    }

    public function visitorTracking()
    {
        $settings = objModel::first();

        return $this->successResponse(
            $settings ? $settings->visitorTrackingPayload() : [
                'enabled' => false,
                'placement' => 'body',
                'site_key' => null,
                'script_url' => null,
                'embed_code' => null,
                'body' => null,
                'allowed_domains' => [],
            ],
            __('api.All items'),
            200
        );
    }

    /**
     * Redirect announcement image requests to a fresh storage URL.
     *
     * The optional version only busts browser caches. The current database value
     * is deliberately used even for an older version so cached site settings can
     * never keep pointing at an object that was replaced and deleted.
     */
    public function announcementImage(?string $version = null)
    {
        abort_unless(Schema::hasTable('new_settings'), 404);

        $storedPath = (string) (new NewSettings())->getSetting('popup_image');
        abort_if($storedPath === '', 404);

        $imageUrl = (string) showFile($storedPath);
        abort_unless(preg_match('#^https?://#i', $imageUrl), 404);

        return redirect()->away($imageUrl, 302, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
       //
    }
}
