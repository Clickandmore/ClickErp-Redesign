<?php

namespace App\Http\Actions\ingaz;
use App\Http\Actions\MainAction;
use App\Models\ingaz\UserFirebaseToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserFirebaseTokenAction extends MainAction
{
    public function __construct(UserFirebaseToken $model)
    {
        $this->model = $model;
    }

    public function storeUserFirebaseToken($data, $request)
    {
        $userId = Auth::user()->id;
        $data['user_id'] = $userId;

        // نطابق على التوكن نفسه لا على نوع الجهاز: المستخدم قد يملك عدة أجهزة
        // بنفس المنصة، والمطابقة على software_type وحده كانت ستمسح توكن جهازه
        // الآخر عند كل تسجيل دخول.
        $this->model->newQuery()->updateOrCreate(
            [
                'user_id' => $userId,
                'phone_token' => $data['phone_token'],
            ],
            [
                'software_type' => $data['software_type'],
            ]
        );

        // نفس الجهاز قد يُصدر توكناً جديداً بعد إعادة تثبيت التطبيق، فنحذف أي
        // توكن قديم صار مملوكاً لمستخدم آخر (تسجيل دخول بحساب مختلف على الجهاز).
        $this->model->newQuery()
            ->where('phone_token', $data['phone_token'])
            ->where('user_id', '!=', $userId)
            ->delete();

        return $data;
    }

    public function updateUserFirebaseToken($id, $data)
    {
        return $this->update($id,$data);
    }


}


