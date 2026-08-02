<?php

namespace App\Http\Controllers\APPAPIControllers;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Complaint;
use App\Services\ManagedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AppComplaintController extends Controller
{
    public function addComplaint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return jsonValid($validator->errors(), $validator->errors()->first());
        }

        $data = [
            'user_id' => auth('api')->id(),
            'address' => $request->address,
            'message' => $request->message,
            'details' => $request->message,
            'complainant_name' => (string) (auth('api')->user()?->name ?? ''),
            'complainant_phone' => (string) (auth('api')->user()?->phone ?? ''),
        ];

        if ($request->hasFile('file')) {
            $data['file'] = app(ManagedFileStorage::class)->uploadFile(
                $request->file('file'),
                'complaints'
            );
        }

        try {
            $complaint = Complaint::create($data);
        } catch (\Throwable $exception) {
            if (!empty($data['file'])) {
                deleteFile($data['file']);
            }
            throw $exception;
        }

        return jsonSuccess($complaint->fresh(), 'تم ارسال الشكوى بنجاح');
    }
}
