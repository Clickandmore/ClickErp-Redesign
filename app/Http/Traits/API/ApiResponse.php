<?php

namespace App\Http\Traits\API;

trait ApiResponse
{
    public function successResponse($data = null, $message = null, $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }

    public function errorResponse($message = null, $code = 400, $data = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }
}
