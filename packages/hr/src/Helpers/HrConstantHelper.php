<?php

namespace Nami\Hr\Helpers;

class HrConstantHelper
{
    public const filesPath = "/app/public/";

    public const imageCompress = false ;

    public const globalArray = [
        "project_language" => [
            'ar' => 'auth.ar',
            'en' => 'auth.en',
        ],
        "allowance_typeis" => [
            '1' => [
                "ar" => "يوم",
                "en" => "day"
            ],
            '2' => [
                "ar" => "قيمة",
                "en" => "VALUE"
            ],
            '3' => [
                "ar" => "نسبه %",
                "en" => "PERCENTAGE"
            ],
        ],
        "employee_vacation_status" => [
            '0' => [
                "ar" => "جديد",
                "en" => "new"
            ],
            '1' => [
                "ar" => "مقبول",
                "en" => "accepted"
            ],
            '2' => [
                "ar" => "مرفوض",
                "en" => "refused"
            ],
        ],
        "employee_attendances_type" => [
            '1' => [
                "ar" => "حضور و انصراف",
                "en" => "Attendance and departure"
            ],
            '2' => [
                "ar" => "حضور",
                "en" => "Attendance"
            ],
            '3' => [
                "ar" => "غياب",
                "en" => "absence"
            ],
            '4' => [
                "ar" => "عطلة نهاية الاسبوع",
                "en" => "Weekend"
            ],
            '5' => [
                "ar" => "متأخر",
                "en" => "Late"
            ],
            '6' => [
                "ar" => "أجازة",
                "en" => "Vacation"
            ],
            '7' => [
                "ar" => "الإجازة الرسمية",
                "en" => "Official Vacation"
            ],
        ],
    ];


}
