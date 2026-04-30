<?php

namespace App\Http\Controllers\APPAPIControllers;

use App\Http\Controllers\Controller;
use App\Models\ingaz\Complaint;
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
            $file = $request->file('file');
            $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/complaints', $fileName);
            $data['file'] = 'complaints/' . $fileName;
        }

        $complaint = Complaint::create($data);

        return jsonSuccess($complaint->fresh(), 'تم ارسال الشكوى بنجاح');
    }
}
