<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ClientImageService as objService;

class ClientFilesController extends Controller
{
    public string $mainRoute = "client-files";

    public function index(Request $request)
    {

    }

    public function create(Request $request)
    {

    }

    public function store(Request $request)
    {

    }

    public function show(int $id, Request $request)
    {

    }

    public function edit(int $id, Request $request)
    {

    }

    public function update(Request $request, int $id)
    {

    }

    public function destroy(int $id, objService $service)
    {
        $service->deleteWithFile($id, 'image');
        return jsonSuccess();
    }
}
